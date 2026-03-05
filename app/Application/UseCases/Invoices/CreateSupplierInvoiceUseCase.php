<?php

namespace App\Application\UseCases\Invoices;

use App\Models\InventoryMovement;
use App\Models\ProductInventory;
use App\Models\SupplierInvoice;
use App\Models\SupplierInvoiceItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class CreateSupplierInvoiceUseCase
{
    /**
     * @param array{
     *   invoice_no:string,
     *   supplier_name:string,
     *   delivered_at:string,
     *   due_at?:string|null,
     *   note?:string|null,
     *   items: array<int, array{product_id:int, qty:int, total_cost:int}>
     * } $input
     */
    public function execute(array $input): int
    {
        return DB::transaction(function () use ($input) {
            $items = $input['items'] ?? [];
            if (count($items) < 1) {
                throw new \InvalidArgumentException('Minimal 1 item.');
            }

            $deliveredAt = CarbonImmutable::parse($input['delivered_at'])->startOfDay();
            $dueAt = isset($input['due_at']) && $input['due_at']
                ? CarbonImmutable::parse($input['due_at'])->startOfDay()
                : $this->calcDueAt($deliveredAt);

            // buat invoice dulu (grand_total di-update setelah item dibuat)
            $invoice = SupplierInvoice::query()->create([
                'invoice_no'    => $input['invoice_no'],
                'supplier_name' => $input['supplier_name'],
                'delivered_at'  => $deliveredAt->toDateString(),
                'due_at'        => $dueAt->toDateString(),
                'is_paid'       => false,
                'paid_at'       => null,
                'grand_total'   => 0,
                'note'          => $input['note'] ?? null,
            ]);

            $grandTotal = 0;

            // agregasi per product untuk hitung avg_cost berbasis total_cost (sumber kebenaran)
            $agg = []; // product_id => ['qty'=>..., 'total_cost'=>...]
            $rowsToCreate = [];

            foreach ($items as $row) {
                $productId = (int) $row['product_id'];
                $qty = (int) $row['qty'];
                $totalCost = (int) $row['total_cost'];

                if ($qty <= 0) throw new \InvalidArgumentException('Qty harus > 0.');
                if ($totalCost <= 0) throw new \InvalidArgumentException('Total cost harus > 0.');

                // unit_cost hanya referensi display (per rupiah)
                $unitCost = (int) round($totalCost / $qty, 0, PHP_ROUND_HALF_UP);

                $rowsToCreate[] = [
                    'supplier_invoice_id' => $invoice->id,
                    'product_id'          => $productId,
                    'qty'                 => $qty,
                    'total_cost'          => $totalCost,
                    'unit_cost'           => $unitCost,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ];

                $grandTotal += $totalCost;

                if (!isset($agg[$productId])) {
                    $agg[$productId] = ['qty' => 0, 'total_cost' => 0];
                }
                $agg[$productId]['qty'] += $qty;
                $agg[$productId]['total_cost'] += $totalCost;
            }

            SupplierInvoiceItem::query()->insert($rowsToCreate);

            // update inventory per product (lock row)
            foreach ($agg as $productId => $a) {
                /** @var ProductInventory $inv */
                $inv = ProductInventory::query()
                    ->lockForUpdate()
                    ->findOrFail($productId);

                $oldOnHand = (int) $inv->on_hand_qty;
                $oldAvg = (int) $inv->avg_cost;

                $addQty = (int) $a['qty'];
                $addTotalCost = (int) $a['total_cost'];

                $newOnHand = $oldOnHand + $addQty;

                // moving average berbasis total_cost (sumber kebenaran)
                $totalValueBefore = $oldOnHand * $oldAvg;
                $totalValueAfter = $totalValueBefore + $addTotalCost;

                $rawAvg = $newOnHand > 0 ? ($totalValueAfter / $newOnHand) : 0;
                $snappedAvg = $this->snapTo1000($rawAvg);

                $inv->on_hand_qty = $newOnHand;
                $inv->avg_cost = $snappedAvg;
                $inv->save();
            }

            // ledger movement per item (detail tetap per item)
            $createdItems = SupplierInvoiceItem::query()
                ->where('supplier_invoice_id', $invoice->id)
                ->get(['id','product_id','qty','unit_cost']);

            foreach ($createdItems as $it) {
                InventoryMovement::query()->create([
                    'product_id' => (int) $it->product_id,
                    'type'       => 'invoice_in',
                    'qty'        => (int) $it->qty, // +
                    'unit_cost'  => (int) $it->unit_cost, // referensi
                    'ref_type'   => 'supplier_invoice',
                    'ref_id'     => (int) $invoice->id,
                    'note'       => $invoice->invoice_no,
                ]);
            }

            $invoice->grand_total = $grandTotal;
            $invoice->save();

            return $invoice->id;
        });
    }

    private function calcDueAt(CarbonImmutable $deliveredAt): CarbonImmutable
    {
        // rule kamu: tanggal sama bulan depan, kalau tanggal tidak ada → akhir bulan
        return $deliveredAt->addMonthNoOverflow();
    }

    private function snapTo1000(float $value): int
    {
        // A1 1000: avg_cost disnap ke kelipatan 1000 terdekat (round half up)
        return (int) (round($value / 1000, 0, PHP_ROUND_HALF_UP) * 1000);
    }
}
