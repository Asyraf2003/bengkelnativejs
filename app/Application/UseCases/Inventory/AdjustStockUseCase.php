<?php

namespace App\Application\UseCases\Inventory;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\StockAdjustment;
use Illuminate\Support\Facades\DB;

class AdjustStockUseCase
{
    /**
     * @param array{product_id:int, qty_delta:int, reason:string, user_id:int} $input
     */
    public function execute(array $input): int
    {
        return DB::transaction(function () use ($input) {
            /** @var Product $product */
            $product = Product::query()
                ->with('inventory')
                ->lockForUpdate()
                ->findOrFail($input['product_id']);

            $inv = $product->inventory;

            // reason wajib
            $reason = trim($input['reason'] ?? '');
            if ($reason === '') {
                throw new \InvalidArgumentException('Reason wajib diisi.');
            }

            $qtyDelta = (int) $input['qty_delta'];
            if ($qtyDelta === 0) {
                throw new \InvalidArgumentException('Qty tidak boleh 0.');
            }

            // Invariant: on_hand tidak boleh negatif
            $newOnHand = (int) $inv->on_hand_qty + $qtyDelta;
            if ($newOnHand < 0) {
                throw new \DomainException('Stok tidak cukup untuk pengurangan (on_hand akan negatif).');
            }

            // Simpan adjustment
            $adj = StockAdjustment::query()->create([
                'product_id'  => $product->id,
                'qty_delta'   => $qtyDelta,
                'reason'      => $reason,
                'created_by'  => (int) $input['user_id'],
            ]);

            // Update inventory
            $inv->on_hand_qty = $newOnHand;
            $inv->save();

            // Ledger
            $type = $qtyDelta > 0 ? 'adjust_in' : 'adjust_out';

            InventoryMovement::query()->create([
                'product_id' => $product->id,
                'type'       => $type,
                'qty'        => $qtyDelta, // signed
                'unit_cost'  => null,
                'ref_type'   => 'stock_adjustment',
                'ref_id'     => $adj->id,
                'note'       => $reason,
            ]);

            return $adj->id;
        });
    }
}
