<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerTransaction;
use App\Models\CustomerTransactionLine;
use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RefundCustomerTransactionUseCase
{
    /**
     * @param array{
     *   transaction_id:int,
     *   refunded_at:string,
     *   refund_amount:int,
     *   items: array<int, array{line_id:int, qty:int}>
     * } $input
     */
    public function execute(array $input): void
    {
        DB::transaction(function () use ($input) {
            $trx = CustomerTransaction::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail((int) $input['transaction_id']);

            if ($trx->status !== 'paid') {
                throw new \DomainException('Refund hanya untuk transaksi status paid.');
            }

            $alreadyRefunded = $trx->refunded_at !== null
                || (int) $trx->refund_amount > 0
                || $trx->lines->contains(fn ($ln) => (int) $ln->refunded_qty > 0);

            if ($alreadyRefunded) {
                throw new \DomainException('Refund untuk transaksi ini hanya boleh sekali.');
            }

            $refundedAt = CarbonImmutable::parse($input['refunded_at'])->startOfDay();

            $refundAmount = (int) ($input['refund_amount'] ?? 0);
            if ($refundAmount <= 0) {
                throw new \InvalidArgumentException('refund_amount harus > 0.');
            }

            $items = $input['items'] ?? [];
            if (count($items) < 1) {
                throw new \InvalidArgumentException('Minimal 1 line untuk refund.');
            }

            $lineMap = [];
            foreach ($trx->lines as $ln) {
                $lineMap[(int) $ln->id] = $ln;
            }

            $aggIn = []; // product_id => qty_sum

            foreach ($items as $it) {
                $lineId = (int) $it['line_id'];
                $qtyRefund = (int) $it['qty'];

                if ($qtyRefund <= 0) {
                    throw new \InvalidArgumentException('qty refund harus > 0.');
                }

                /** @var CustomerTransactionLine|null $ln */
                $ln = $lineMap[$lineId] ?? null;
                if (!$ln) {
                    throw new \DomainException("Line tidak ditemukan: {$lineId}");
                }

                if (!in_array($ln->kind, ['product_sale', 'service_product'], true)) {
                    throw new \DomainException("Line bukan stok: {$lineId}");
                }

                $qtyLine = (int) $ln->qty;
                $already = (int) $ln->refunded_qty;

                if ($already + $qtyRefund > $qtyLine) {
                    throw new \DomainException("Refund melebihi qty line. line_id={$lineId}");
                }

                if ($ln->sale_unit_cost === null) {
                    throw new \DomainException("sale_unit_cost belum ada (pastikan sudah Paid). line_id={$lineId}");
                }

                $pid = (int) $ln->product_id;
                $aggIn[$pid] = ($aggIn[$pid] ?? 0) + $qtyRefund;
            }

            foreach ($aggIn as $productId => $qtyIn) {
                ProductInventory::query()->lockForUpdate()->findOrFail($productId);
            }

            foreach ($items as $it) {
                $lineId = (int) $it['line_id'];
                $qtyRefund = (int) $it['qty'];

                /** @var CustomerTransactionLine $ln */
                $ln = $lineMap[$lineId];

                $pid = (int) $ln->product_id;
                $unitCost = (int) $ln->sale_unit_cost;

                $ln->refunded_qty = (int) $ln->refunded_qty + $qtyRefund;
                $ln->save();

                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($pid);
                $inv->on_hand_qty = (int) $inv->on_hand_qty + $qtyRefund;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $pid,
                    'type'       => 'refund_in',
                    'qty'        => $qtyRefund,
                    'unit_cost'  => $unitCost,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Refund in (once per transaction)',
                ]);
            }

            $trx->refunded_at = $refundedAt->toDateString();
            $trx->refund_amount = $refundAmount;
            $trx->save();
        });
    }
}
