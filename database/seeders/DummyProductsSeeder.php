<?php

namespace Database\Seeders;

use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DummyProductsSeeder extends Seeder
{
    public function run(): void
    {
        $seed  = (int) env('DUMMY_SEED', 20260305);
        $count = (int) env('DUMMY_PRODUCTS_COUNT', 20);

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        $brands = ['Aspira', 'Federal', 'Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'NGK', 'KTC', 'Nippon'];
        $sizes  = ['S', 'M', 'L', 'XL', '100ml', '250ml', '500ml', '1L'];

        for ($i = 1; $i <= $count; $i++) {
            $code = sprintf('PRD-%04d', $i);

            $name = 'Sparepart ' . $faker->unique()->word() . ' ' . $faker->randomElement(['Ori', 'KW', 'Premium']);
            $brand = $faker->randomElement($brands);
            $size  = $faker->randomElement($sizes);

            $salePrice = (int) (round($faker->numberBetween(5000, 250000) / 1000) * 1000);

            Product::query()->updateOrCreate(
                ['code' => $code],
                [
                    'name'       => $name,
                    'brand'      => $brand,
                    'size'       => $size,
                    'sale_price' => $salePrice,
                    'is_active'  => true,
                ]
            );
            // inventory row otomatis dibuat via Product::created hook
            // stok tetap 0 sesuai blueprint (stok naik hanya lewat faktur)
        }
    }
}
