<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerTransaction;
use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class MarkPaidCustomerTransactionUseCase
{
    /**
     * @param array{transaction_id:int, paid_at:string} $input
     */
    public function execute(array $input): void
    {
        DB::transaction(function () use ($input) {
            $trx = CustomerTransaction::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail((int) $input['transaction_id']);

            if ($trx->status !== 'draft') {
                throw new \DomainException('Hanya draft yang boleh di-mark paid.');
            }

            $paidAt = CarbonImmutable::parse($input['paid_at'])->startOfDay();

            // agregasi qty stok per product
            $agg = [];
            foreach ($trx->lines as $ln) {
                if (in_array($ln->kind, ['product_sale','service_product'], true)) {
                    $pid = (int) $ln->product_id;
                    $q = (int) $ln->qty;
                    $agg[$pid] = ($agg[$pid] ?? 0) + $q;
                }
            }

            // lock inventory + validate reserved cukup + on_hand cukup
            foreach ($agg as $productId => $qty) {
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                if ((int) $inv->reserved_qty < $qty) {
                    throw new \DomainException("Reserved tidak cukup. product_id={$productId}");
                }
                if ((int) $inv->on_hand_qty < $qty) {
                    throw new \DomainException("On hand tidak cukup. product_id={$productId}");
                }
            }

            // apply per line (cogs + sale_unit_cost) menggunakan avg_cost saat paid
            foreach ($trx->lines as $ln) {
                if (!in_array($ln->kind, ['product_sale','service_product'], true)) {
                    continue;
                }

                $pid = (int) $ln->product_id;
                $qty = (int) $ln->qty;

                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($pid);
                $avg = (int) $inv->avg_cost;

                $ln->cogs_amount = $qty * $avg;
                $ln->sale_unit_cost = $avg; // untuk refund_in valuation konsisten
                $ln->save();

                // ledger sale_out per line
                InventoryMovement::query()->create([
                    'product_id' => $pid,
                    'type'       => 'sale_out',
                    'qty'        => -1 * $qty,
                    'unit_cost'  => $avg,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Paid sale out',
                ]);
            }

            // apply inventory totals (reserved -> release + on_hand reduce)
            foreach ($agg as $productId => $qty) {
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                $inv->reserved_qty = (int) $inv->reserved_qty - $qty;
                $inv->on_hand_qty  = (int) $inv->on_hand_qty - $qty;

                if ((int) $inv->reserved_qty < 0 || (int) $inv->on_hand_qty < 0) {
                    throw new \DomainException("Invariant breach. product_id={$productId}");
                }

                $inv->save();
            }

            $trx->status = 'paid';
            $trx->paid_at = $paidAt->toDateString();
            $trx->save();
        });
    }
}
