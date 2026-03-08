<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerOrder;
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
     *  customer_order_id?:int|null,
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

            $reserveAgg = [];
            $lineRows = [];

            foreach ($lines as $ln) {
                $kind = (string) ($ln['kind'] ?? '');
                if (!in_array($kind, ['product_sale', 'service_fee', 'service_product', 'outside_cost'], true)) {
                    throw new \InvalidArgumentException("kind invalid: {$kind}");
                }

                $amount = (int) ($ln['amount'] ?? 0);
                if ($amount < 0) {
                    throw new \InvalidArgumentException('amount tidak boleh negatif (expense tetap input positif).');
                }

                $productId = isset($ln['product_id']) ? (int) $ln['product_id'] : null;
                $qty = isset($ln['qty']) ? (int) $ln['qty'] : null;

                $usesStock = in_array($kind, ['product_sale', 'service_product'], true);

                if ($usesStock) {
                    if (!$productId) {
                        throw new \InvalidArgumentException('product_id wajib untuk line stok.');
                    }

                    if (!$qty || $qty <= 0) {
                        throw new \InvalidArgumentException('qty wajib > 0 untuk line stok.');
                    }

                    $reserveAgg[$productId] = ($reserveAgg[$productId] ?? 0) + $qty;
                } else {
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

            foreach ($reserveAgg as $productId => $reserveQty) {
                /** @var ProductInventory $inv */
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                $available = (int) $inv->on_hand_qty - (int) $inv->reserved_qty;
                if ($available < $reserveQty) {
                    throw new \DomainException("Stok tidak cukup untuk reserve. product_id={$productId}, available={$available}, need={$reserveQty}");
                }
            }

            $customerOrderId = isset($input['customer_order_id']) && $input['customer_order_id']
                ? (int) $input['customer_order_id']
                : null;

            if ($customerOrderId) {
                $order = CustomerOrder::query()->findOrFail($customerOrderId);
            } else {
                $order = CustomerOrder::query()->create([
                    'customer_name' => $customerName,
                    'note' => $input['note'] ?? null,
                ]);

                $customerOrderId = (int) $order->id;
            }

            $trx = CustomerTransaction::query()->create([
                'customer_order_id' => $customerOrderId,
                'customer_name'     => $customerName,
                'status'            => 'draft',
                'transacted_at'     => $transactedAt->toDateString(),
                'paid_at'           => null,
                'refunded_at'       => null,
                'refund_amount'     => 0,
                'note'              => $input['note'] ?? null,
            ]);

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

            foreach ($reserveAgg as $productId => $reserveQty) {
                $inv = ProductInventory::query()->lockForUpdate()->findOrFail($productId);

                $inv->reserved_qty = (int) $inv->reserved_qty + (int) $reserveQty;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $productId,
                    'type'       => 'reserve',
                    'qty'        => (int) $reserveQty,
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
