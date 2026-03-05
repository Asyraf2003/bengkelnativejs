<?php

namespace Database\Seeders;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyProductsSeeder extends Seeder
{
    public function run(): void
    {
        $seed  = (int) env('DUMMY_SEED', 20260305); // fallback kalau belum di-set
        $count = (int) env('DUMMY_PRODUCTS_COUNT', 20);

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        // Bersihkan movement dummy agar re-run seed tidak dobel-dobel
        InventoryMovement::query()
            ->where('ref_type', 'seed')
            ->delete();

        $brands = ['Aspira', 'Federal', 'Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'NGK', 'KTC', 'Nippon'];
        $sizes  = ['S', 'M', 'L', 'XL', '100ml', '250ml', '500ml', '1L'];

        for ($i = 1; $i <= $count; $i++) {
            $code = sprintf('PRD-%04d', $i);

            $name = 'Sparepart ' . $faker->unique()->word() . ' ' . $faker->randomElement(['Ori', 'KW', 'Premium']);
            $brand = $faker->randomElement($brands);
            $size  = $faker->randomElement($sizes);

            $salePrice = (int) (round($faker->numberBetween(5000, 250000) / 1000) * 1000);

            $product = Product::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name'       => $name,
                    'brand'      => $brand,
                    'size'       => $size,
                    'sale_price' => $salePrice,
                    'is_active'  => true,
                ]
            );

            // Pastikan inventory row ada (kalau product sudah ada sebelum boot event)
            $inv = $product->inventory()->first();
            if (!$inv) {
                $inv = ProductInventory::query()->create([
                    'product_id'   => $product->id,
                    'on_hand_qty'  => 0,
                    'reserved_qty' => 0,
                    'avg_cost'     => 0,
                ]);
            }

            // Stok awal (dummy) + avg_cost (dummy)
            $onHand = $faker->numberBetween(0, 50);
            $avgCost = $onHand === 0
                ? 0
                : (int) (round($faker->numberBetween(1000, max(1000, (int) ($salePrice * 0.7))) / 1000) * 1000);

            $inv->on_hand_qty = $onHand;
            $inv->reserved_qty = 0;
            $inv->avg_cost = $avgCost;
            $inv->save();

            if ($onHand > 0) {
                InventoryMovement::query()->create([
                    'product_id' => $product->id,
                    'type'       => 'adjust_in',
                    'qty'        => $onHand, // signed (+)
                    'unit_cost'  => $avgCost > 0 ? $avgCost : null,
                    'ref_type'   => 'seed',
                    'ref_id'     => $seed,
                    'note'       => "Seed initial stock (seed={$seed})",
                ]);
            }
        }
    }
}
