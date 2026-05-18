<?php

declare(strict_types=1);

namespace App\Application\Note\Services;

use App\Application\Inventory\Services\IssueInventoryOperation;
use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\WorkItem;
use App\Ports\Out\Note\WorkItemWriterPort;

final class CreateTransactionWorkspaceWorkItemPersister
{
    public function __construct(
        private readonly WorkItemWriterPort $workItems,
        private readonly IssueInventoryOperation $issueInventory,
        private readonly WorkItemFactory $factory,
        private readonly CreateTransactionWorkspaceWorkItemPayloadMapper $mapper,
    ) {
    }

    /**
     * @param mixed $items
     */
    public function persist(Note $note, mixed $items, int $startLineNo = 1): CreateTransactionWorkspacePersistResult
    {
        if (! is_array($items)) {
            return new CreateTransactionWorkspacePersistResult(0, []);
        }

        $lineNo = max(1, $startLineNo);
        $created = 0;
        $packageAllocations = [];

        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            [$type, $sd, $ext, $sto] = $this->mapper->map($item);
            $workItem = $this->factory->build($note->id(), $lineNo, $type, $sd, $ext, $sto);

            $note->addWorkItem($workItem);
            $this->workItems->create($workItem);

            foreach ($workItem->storeStockLines() as $line) {
                $this->issueInventory->execute(
                    $line->productId(),
                    $line->qty(),
                    $note->transactionDate(),
                    'work_item_store_stock_line',
                    $line->id()
                );
            }

            $allocation = $this->packageAllocationFrom($item, $workItem);

            if ($allocation !== null) {
                $packageAllocations[] = $allocation;
            }

            $lineNo++;
            $created++;
        }

        return new CreateTransactionWorkspacePersistResult($created, $packageAllocations);
    }

    /**
     * @param array<string, mixed> $item
     * @return array{
     *     work_item_id:string,
     *     store_stock_line_id:string,
     *     pricing_mode:string,
     *     package_total_rupiah:int,
     *     sparepart_total_rupiah:int,
     *     service_price_rupiah:int,
     *     product_id:string,
     *     qty:int,
     *     product_unit_price_rupiah:int
     * }|null
     */
    private function packageAllocationFrom(array $item, WorkItem $workItem): ?array
    {
        if (($item['pricing_mode'] ?? null) !== 'package_auto_split') {
            return null;
        }

        $serviceDetail = $workItem->serviceDetail();
        $storeStockLine = $workItem->storeStockLines()[0] ?? null;

        if ($serviceDetail === null || $storeStockLine === null) {
            return null;
        }

        $qty = $storeStockLine->qty();
        $sparepartTotal = $storeStockLine->lineTotalRupiah()->amount();

        return [
            'work_item_id' => $workItem->id(),
            'store_stock_line_id' => $storeStockLine->id(),
            'pricing_mode' => 'package_auto_split',
            'package_total_rupiah' => (int) ($item['package_total_rupiah'] ?? 0),
            'sparepart_total_rupiah' => $sparepartTotal,
            'service_price_rupiah' => $serviceDetail->servicePriceRupiah()->amount(),
            'product_id' => $storeStockLine->productId(),
            'qty' => $qty,
            'product_unit_price_rupiah' => intdiv($sparepartTotal, max(1, $qty)),
        ];
    }
}
