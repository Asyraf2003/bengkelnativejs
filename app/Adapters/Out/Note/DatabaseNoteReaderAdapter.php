<?php

declare(strict_types=1);

namespace App\Adapters\Out\Note;

use App\Core\Note\Note\Note;
use App\Core\Note\WorkItem\ExternalPurchaseLine;
use App\Core\Note\WorkItem\ServiceDetail;
use App\Core\Note\WorkItem\StoreStockLine;
use App\Core\Note\WorkItem\WorkItem;
use App\Core\Shared\Exceptions\DomainException;
use App\Core\Shared\ValueObjects\Money;
use App\Ports\Out\Note\NoteReaderPort;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseNoteReaderAdapter implements NoteReaderPort
{
    public function getById(string $id): ?Note
    {
        $normalizedId = trim($id);

        if ($normalizedId === '') {
            throw new DomainException('Note id wajib ada.');
        }

        $noteRow = DB::table('notes')
            ->where('id', $normalizedId)
            ->first();

        if ($noteRow === null) {
            return null;
        }

        $workItemRows = DB::table('work_items')
            ->where('note_id', $normalizedId)
            ->orderBy('line_no')
            ->get()
            ->all();

        $workItemIds = array_map(
            static fn (stdClass $row): string => (string) $row->id,
            $workItemRows,
        );

        $serviceDetailsByWorkItemId = $this->loadServiceDetailsByWorkItemId($workItemIds);
        $externalPurchaseLinesByWorkItemId = $this->loadExternalPurchaseLinesByWorkItemId($workItemIds);
        $storeStockLinesByWorkItemId = $this->loadStoreStockLinesByWorkItemId($workItemIds);

        $workItems = [];

        foreach ($workItemRows as $workItemRow) {
            $workItemId = (string) $workItemRow->id;
            $transactionType = (string) $workItemRow->transaction_type;

            $serviceDetail = $serviceDetailsByWorkItemId[$workItemId] ?? null;
            $externalPurchaseLines = $externalPurchaseLinesByWorkItemId[$workItemId] ?? [];
            $storeStockLines = $storeStockLinesByWorkItemId[$workItemId] ?? [];

            if (
                $transactionType === WorkItem::TYPE_SERVICE_ONLY
                || $transactionType === WorkItem::TYPE_SERVICE_WITH_EXTERNAL_PURCHASE
            ) {
                if ($serviceDetail === null) {
                    throw new DomainException('Service detail pada work item tidak ditemukan.');
                }
            }

            $workItems[] = WorkItem::rehydrate(
                $workItemId,
                (string) $workItemRow->note_id,
                (int) $workItemRow->line_no,
                $transactionType,
                (string) $workItemRow->status,
                Money::fromInt((int) $workItemRow->subtotal_rupiah),
                $serviceDetail,
                $externalPurchaseLines,
                $storeStockLines,
            );
        }

        return Note::rehydrate(
            (string) $noteRow->id,
            (string) $noteRow->customer_name,
            $this->parseDate((string) $noteRow->transaction_date),
            Money::fromInt((int) $noteRow->total_rupiah),
            $workItems,
        );
    }

    /**
     * @param list<string> $workItemIds
     * @return array<string, ServiceDetail>
     */
    private function loadServiceDetailsByWorkItemId(array $workItemIds): array
    {
        if ($workItemIds === []) {
            return [];
        }

        $rows = DB::table('work_item_service_details')
            ->whereIn('work_item_id', $workItemIds)
            ->get()
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $result[(string) $row->work_item_id] = ServiceDetail::rehydrate(
                (string) $row->service_name,
                Money::fromInt((int) $row->service_price_rupiah),
                (string) $row->part_source,
            );
        }

        return $result;
    }

    /**
     * @param list<string> $workItemIds
     * @return array<string, list<ExternalPurchaseLine>>
     */
    private function loadExternalPurchaseLinesByWorkItemId(array $workItemIds): array
    {
        if ($workItemIds === []) {
            return [];
        }

        $rows = DB::table('work_item_external_purchase_lines')
            ->whereIn('work_item_id', $workItemIds)
            ->orderBy('work_item_id')
            ->orderBy('id')
            ->get()
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $workItemId = (string) $row->work_item_id;

            if (array_key_exists($workItemId, $result) === false) {
                $result[$workItemId] = [];
            }

            $result[$workItemId][] = ExternalPurchaseLine::rehydrate(
                (string) $row->id,
                (string) $row->cost_description,
                Money::fromInt((int) $row->unit_cost_rupiah),
                (int) $row->qty,
            );
        }

        return $result;
    }

    /**
     * @param list<string> $workItemIds
     * @return array<string, list<StoreStockLine>>
     */
    private function loadStoreStockLinesByWorkItemId(array $workItemIds): array
    {
        if ($workItemIds === []) {
            return [];
        }

        $rows = DB::table('work_item_store_stock_lines')
            ->whereIn('work_item_id', $workItemIds)
            ->orderBy('work_item_id')
            ->orderBy('id')
            ->get()
            ->all();

        $result = [];

        foreach ($rows as $row) {
            $workItemId = (string) $row->work_item_id;

            if (array_key_exists($workItemId, $result) === false) {
                $result[$workItemId] = [];
            }

            $result[$workItemId][] = StoreStockLine::rehydrate(
                (string) $row->id,
                (string) $row->product_id,
                (int) $row->qty,
                Money::fromInt((int) $row->line_total_rupiah),
            );
        }

        return $result;
    }

    private function parseDate(string $value): DateTimeImmutable
    {
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($parsed === false || $parsed->format('Y-m-d') !== trim($value)) {
            throw new DomainException('Transaction date pada note harus berupa tanggal valid dengan format Y-m-d.');
        }

        return $parsed;
    }
}
