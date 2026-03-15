<?php

declare(strict_types=1);

namespace App\Application\Inventory\UseCases;

use App\Application\Inventory\Services\IssueInventoryOperation;
use App\Application\Shared\DTO\Result;
use App\Core\Shared\Exceptions\DomainException;
use App\Ports\Out\AuditLogPort;
use App\Ports\Out\TransactionManagerPort;
use DateTimeImmutable;
use Throwable;

final class IssueInventoryHandler
{
    public function __construct(
        private readonly IssueInventoryOperation $operation,
        private readonly TransactionManagerPort $transactions,
        private readonly AuditLogPort $audit
    ) {}

    public function handle(string $pId, int $qty, string $tgl, string $sType, string $sId): Result
    {
        $started = false;
        try {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($tgl)) ?: throw new DomainException('Tanggal tidak valid.');
            
            $this->transactions->begin(); $started = true;
            $res = $this->operation->execute($pId, $qty, $date, $sType, $sId);

            $this->audit->record('inventory_issued', [
                'product_id' => $pId,
                'qty' => $qty,
                'source' => "$sType:$sId"
            ]);

            $this->transactions->commit();
            return Result::success($this->map($res), 'Inventory issue berhasil.');
        } catch (DomainException $e) {
            if ($started) $this->transactions->rollBack();
            return Result::failure($e->getMessage(), ['inventory' => ['INVALID_INVENTORY_ISSUE']]);
        } catch (Throwable $e) {
            if ($started) $this->transactions->rollBack();
            throw $e;
        }
    }

    private function map(array $res): array
    {
        return [
            'movement_id' => $res['movement']->id(),
            'qty_on_hand' => $res['product_inventory']->qtyOnHand(),
            'avg_cost' => $res['product_inventory_costing']->avgCostRupiah()->amount()
        ];
    }
}
