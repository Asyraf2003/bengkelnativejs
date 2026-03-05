<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerTransaction;
use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;

class CancelDraftCustomerTransactionUseCase
{
    public function execute(int $transactionId): void
    {
        DB::transaction(function () use ($transactionId) {
            $trx = CustomerTransaction::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($transactionId);

            if ($trx->status !== 'draft') {
                throw new \DomainException('Hanya draft yang boleh di-cancel.');
            }

            // agregasi reserve yg harus dilepas
            $releaseAgg = [];
            foreach ($trx->lines as $ln) {
                if (in_array($ln->kind, ['product_sale','service_product'], true)) {
                    $pid = (int) $ln->product_id;
                    $q = (int) $ln->qty;
                    $releaseAgg[$pid] = ($releaseAgg[$pid] ?? 0) + $q;
                }
            }

            foreach ($releaseAgg as $productId => $releaseQty) {
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                $newReserved = (int) $inv->reserved_qty - (int) $releaseQty;
                if ($newReserved < 0) {
                    throw new \DomainException("Reserved akan negatif. product_id={$productId}");
                }

                $inv->reserved_qty = $newReserved;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $productId,
                    'type'       => 'release',
                    'qty'        => -1 * (int) $releaseQty, // - release
                    'unit_cost'  => null,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Cancel draft release',
                ]);
            }

            $trx->status = 'canceled';
            $trx->save();
        });
    }
}
