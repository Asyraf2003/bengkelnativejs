<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerTransaction;
use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use Illuminate\Support\Facades\DB;

class DeleteDraftCustomerTransactionLineUseCase
{
    public function execute(int $transactionId, int $lineId): void
    {
        DB::transaction(function () use ($transactionId, $lineId) {
            $trx = CustomerTransaction::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail($transactionId);

            if ($trx->status !== 'draft') {
                throw new \DomainException('Hanya draft yang boleh diubah line-nya.');
            }

            if ($trx->lines->count() <= 1) {
                throw new \DomainException('Draft minimal harus punya 1 line. Jika ingin batalkan semua, gunakan cancel draft.');
            }

            $line = $trx->lines->firstWhere('id', $lineId);
            if (!$line) {
                throw new \DomainException("Line tidak ditemukan pada transaksi ini. line_id={$lineId}");
            }

            if ($line->usesStock()) {
                $productId = (int) $line->product_id;
                $qty = (int) $line->qty;

                $inv = ProductInventory::query()
                    ->lockForUpdate()
                    ->findOrFail($productId);

                $newReserved = (int) $inv->reserved_qty - $qty;
                if ($newReserved < 0) {
                    throw new \DomainException("Reserved akan negatif. product_id={$productId}");
                }

                $inv->reserved_qty = $newReserved;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $productId,
                    'type'       => 'release',
                    'qty'        => -1 * $qty,
                    'unit_cost'  => null,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Delete draft line release',
                ]);
            }

            $line->delete();
        });
    }
}
