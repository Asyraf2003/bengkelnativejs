<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerTransaction;
use App\Models\CustomerTransactionLine;
use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateDraftCustomerTransactionUseCase
{
    /**
     * @param array{
     *  customer_name:string,
     *  transacted_at:string,
     *  note?:string|null,
     *  lines: array<int, array{
     *    kind:string,
     *    product_id?:int|null,
     *    qty?:int|null,
     *    amount:int,
     *    note?:string|null
     *  }>
     * } $input
     */
    public function execute(array $input): int
    {
        return DB::transaction(function () use ($input) {
            $customerName = trim((string) ($input['customer_name'] ?? ''));
            if ($customerName === '') {
                throw new \InvalidArgumentException('customer_name wajib.');
            }

            $transactedAt = CarbonImmutable::parse($input['transacted_at'])->startOfDay();

            $lines = $input['lines'] ?? [];
            if (count($lines) < 1) {
                throw new \InvalidArgumentException('Minimal 1 line item.');
            }

            // validasi & normalisasi line + agregasi reserve per product
            $reserveAgg = []; // product_id => qty_sum
            $lineRows = [];

            foreach ($lines as $ln) {
                $kind = (string) ($ln['kind'] ?? '');
                if (!in_array($kind, ['product_sale','service_fee','service_product','outside_cost'], true)) {
                    throw new \InvalidArgumentException("kind invalid: {$kind}");
                }

                $amount = (int) ($ln['amount'] ?? 0);
                if ($amount < 0) {
                    throw new \InvalidArgumentException('amount tidak boleh negatif (expense tetap input positif).');
                }

                $productId = isset($ln['product_id']) ? (int) $ln['product_id'] : null;
                $qty = isset($ln['qty']) ? (int) $ln['qty'] : null;

                $usesStock = in_array($kind, ['product_sale','service_product'], true);

                if ($usesStock) {
                    if (!$productId) throw new \InvalidArgumentException('product_id wajib untuk line stok.');
                    if (!$qty || $qty <= 0) throw new \InvalidArgumentException('qty wajib > 0 untuk line stok.');

                    $reserveAgg[$productId] = ($reserveAgg[$productId] ?? 0) + $qty;
                } else {
                    // non-stok: pastikan tidak nyelip qty
                    $productId = null;
                    $qty = null;
                }

                $lineRows[] = [
                    'kind'       => $kind,
                    'product_id' => $productId,
                    'qty'        => $qty,
                    'amount'     => $amount,
                    'note'       => $ln['note'] ?? null,
                ];
            }

            // VALIDASI RESERVE: lock inventory row per product
            foreach ($reserveAgg as $productId => $reserveQty) {
                /** @var ProductInventory $inv */
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                $available = (int) $inv->on_hand_qty - (int) $inv->reserved_qty;
                if ($available < $reserveQty) {
                    throw new \DomainException("Stok tidak cukup untuk reserve. product_id={$productId}, available={$available}, need={$reserveQty}");
                }
            }

            // create transaction
            $trx = CustomerTransaction::query()->create([
                'customer_name' => $customerName,
                'status'        => 'draft',
                'transacted_at' => $transactedAt->toDateString(),
                'paid_at'       => null,
                'refunded_at'   => null,
                'refund_amount' => 0,
                'note'          => $input['note'] ?? null,
            ]);

            // insert lines
            foreach ($lineRows as $row) {
                CustomerTransactionLine::query()->create([
                    'customer_transaction_id' => $trx->id,
                    'kind'       => $row['kind'],
                    'product_id' => $row['product_id'],
                    'qty'        => $row['qty'],
                    'amount'     => $row['amount'],
                    'note'       => $row['note'],
                ]);
            }

            // apply reserve + ledger
            foreach ($reserveAgg as $productId => $reserveQty) {
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                $inv->reserved_qty = (int) $inv->reserved_qty + (int) $reserveQty;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $productId,
                    'type'       => 'reserve',
                    'qty'        => (int) $reserveQty, // + reserve
                    'unit_cost'  => null,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Draft reserve',
                ]);
            }

            return $trx->id;
        });
    }
}
