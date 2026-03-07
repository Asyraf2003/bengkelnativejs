<?php

namespace App\Application\UseCases\Transactions;

use App\Models\CustomerTransaction;
use App\Models\CustomerTransactionLine;
use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdateDraftCustomerTransactionUseCase
{
    /**
     * @param array{
     *  transaction_id:int,
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
    public function execute(array $input): void
    {
        DB::transaction(function () use ($input) {
            $trx = CustomerTransaction::query()
                ->with('lines')
                ->lockForUpdate()
                ->findOrFail((int) $input['transaction_id']);

            if ($trx->status !== 'draft') {
                throw new \DomainException('Hanya draft yang boleh diubah.');
            }

            $customerName = trim((string) ($input['customer_name'] ?? ''));
            if ($customerName === '') {
                throw new \InvalidArgumentException('customer_name wajib.');
            }

            $transactedAt = CarbonImmutable::parse($input['transacted_at'])->startOfDay();

            $lines = $input['lines'] ?? [];
            if (count($lines) < 1) {
                throw new \InvalidArgumentException('Minimal 1 line item.');
            }

            $oldReserveAgg = [];
            foreach ($trx->lines as $oldLine) {
                if ($oldLine->usesStock()) {
                    $pid = (int) $oldLine->product_id;
                    $qty = (int) $oldLine->qty;
                    $oldReserveAgg[$pid] = ($oldReserveAgg[$pid] ?? 0) + $qty;
                }
            }

            $newReserveAgg = [];
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

                    $newReserveAgg[$productId] = ($newReserveAgg[$productId] ?? 0) + $qty;
                } else {
                    $productId = null;
                    $qty = null;
                }

                $lineRows[] = [
                    'kind' => $kind,
                    'product_id' => $productId,
                    'qty' => $qty,
                    'amount' => $amount,
                    'note' => $ln['note'] ?? null,
                ];
            }

            $productIds = array_values(array_unique(array_merge(
                array_keys($oldReserveAgg),
                array_keys($newReserveAgg)
            )));

            $inventories = ProductInventory::query()
                ->whereIn('product_id', $productIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('product_id');

            foreach ($oldReserveAgg as $productId => $releaseQty) {
                /** @var ProductInventory|null $inv */
                $inv = $inventories->get($productId);
                if (!$inv) {
                    throw new \DomainException("Inventory tidak ditemukan. product_id={$productId}");
                }

                $newReserved = (int) $inv->reserved_qty - (int) $releaseQty;
                if ($newReserved < 0) {
                    throw new \DomainException("Reserved akan negatif saat edit draft. product_id={$productId}");
                }

                $inv->reserved_qty = $newReserved;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $productId,
                    'type'       => 'release',
                    'qty'        => -1 * (int) $releaseQty,
                    'unit_cost'  => null,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Edit draft release old reserve',
                ]);
            }

            foreach ($newReserveAgg as $productId => $reserveQty) {
                /** @var ProductInventory|null $inv */
                $inv = $inventories->get($productId);
                if (!$inv) {
                    throw new \DomainException("Inventory tidak ditemukan. product_id={$productId}");
                }

                $available = (int) $inv->on_hand_qty - (int) $inv->reserved_qty;
                if ($available < $reserveQty) {
                    throw new \DomainException("Stok tidak cukup untuk edit draft. product_id={$productId}, available={$available}, need={$reserveQty}");
                }
            }

            $trx->customer_name = $customerName;
            $trx->transacted_at = $transactedAt->toDateString();
            $trx->note = $input['note'] ?? null;
            $trx->save();

            $trx->lines()->delete();

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

            foreach ($newReserveAgg as $productId => $reserveQty) {
                /** @var ProductInventory $inv */
                $inv = $inventories->get($productId);

                $inv->reserved_qty = (int) $inv->reserved_qty + (int) $reserveQty;
                $inv->save();

                InventoryMovement::query()->create([
                    'product_id' => $productId,
                    'type'       => 'reserve',
                    'qty'        => (int) $reserveQty,
                    'unit_cost'  => null,
                    'ref_type'   => 'customer_transaction',
                    'ref_id'     => $trx->id,
                    'note'       => 'Edit draft reserve',
                ]);
            }
        });
    }
}
