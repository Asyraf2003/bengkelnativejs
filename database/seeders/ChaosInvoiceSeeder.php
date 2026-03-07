<?php

namespace Database\Seeders;

use App\Application\UseCases\Invoices\CreateSupplierInvoiceUseCase;
use App\Application\UseCases\Invoices\MarkPaidSupplierInvoiceUseCase;
use App\Models\Product;
use App\Models\SupplierInvoice;
use Carbon\CarbonImmutable;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ChaosInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $seed = (int) env('CHAOS_SEED', 20260307);
        $invoiceMin = (int) env('CHAOS_INVOICES_MIN', 30);
        $invoiceMax = (int) env('CHAOS_INVOICES_MAX', 50);
        $maxItemsPerInvoice = (int) env('CHAOS_INVOICE_MAX_ITEMS', 6);
        $rangeDays = (int) env('CHAOS_RANGE_DAYS', 150);
        $paidRatioPercent = (int) env('CHAOS_INVOICE_PAID_RATIO', 70);

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['id', 'code', 'sale_price']);

        if ($products->count() < 10) {
            throw new \RuntimeException('Product aktif terlalu sedikit untuk chaos invoice seeder.');
        }

        $today = CarbonImmutable::today();
        $start = $today->subDays($rangeDays);

        $invoiceCount = $faker->numberBetween($invoiceMin, $invoiceMax);

        /** @var CreateSupplierInvoiceUseCase $createUseCase */
        $createUseCase = app(CreateSupplierInvoiceUseCase::class);

        /** @var MarkPaidSupplierInvoiceUseCase $markPaidUseCase */
        $markPaidUseCase = app(MarkPaidSupplierInvoiceUseCase::class);

        $supplierNames = [
            'PT Sumber Jaya Motor',
            'CV Prima Onderdil',
            'UD Makmur Sparepart',
            'PT Niaga Roda Abadi',
            'CV Sukses Mandiri Teknik',
            'UD Sentosa Motorindo',
        ];

        $created = 0;
        $markedPaid = 0;

        for ($i = 1; $i <= $invoiceCount; $i++) {
            $deliveredAt = CarbonImmutable::instance(
                $faker->dateTimeBetween($start, $today)
            )->startOfDay();

            $pickedProducts = $products
                ->shuffle()
                ->take($faker->numberBetween(1, max(1, $maxItemsPerInvoice)))
                ->values();

            $items = [];

            foreach ($pickedProducts as $product) {
                $qty = $faker->numberBetween(5, 40);

                // Cost sengaja di bawah sale_price agar margin normal tetap mungkin,
                // tapi tetap bervariasi dan realistis.
                $targetUnitCost = max(
                    1000,
                    (int) round(((int) $product->sale_price * $faker->randomFloat(2, 0.45, 0.90)) / 1000) * 1000
                );

                $totalCost = $qty * $targetUnitCost;

                $items[] = [
                    'product_id' => (int) $product->id,
                    'qty' => (int) $qty,
                    'total_cost' => (int) $totalCost,
                ];
            }

            $invoiceId = $createUseCase->execute([
                'invoice_no' => sprintf(
                    'CHINV-%s-%04d',
                    $deliveredAt->format('Ym'),
                    $i
                ),
                'supplier_name' => $faker->randomElement($supplierNames),
                'delivered_at' => $deliveredAt->toDateString(),
                'due_at' => null,
                'note' => 'chaos invoice seeder',
                'items' => $items,
            ]);

            $created++;

            $shouldBePaid = $faker->numberBetween(1, 100) <= $paidRatioPercent;

            if ($shouldBePaid) {
                /** @var SupplierInvoice $invoice */
                $invoice = SupplierInvoice::query()->findOrFail($invoiceId);

                $dueAt = CarbonImmutable::parse($invoice->due_at)->startOfDay();
                $maxPaidAt = $dueAt->lessThan($today) ? $dueAt : $today;

                if ($maxPaidAt->greaterThanOrEqualTo($deliveredAt)) {
                    $paidAt = CarbonImmutable::instance(
                        $faker->dateTimeBetween($deliveredAt, $maxPaidAt)
                    )->startOfDay();

                    $markPaidUseCase->execute([
                        'invoice_id' => (int) $invoiceId,
                        'paid_at' => $paidAt->toDateString(),
                    ]);

                    $markedPaid++;
                }
            }
        }

        $this->command?->info("Chaos invoices created: {$created}");
        $this->command?->info("Chaos invoices marked paid: {$markedPaid}");
    }
}
