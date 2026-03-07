<?php

namespace Database\Seeders;

use App\Models\Product;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChaosFoundationSeeder extends Seeder
{
    public function run(): void
    {
        $reset = env('CHAOS_RESET', '0') === '1';
        $seed = (int) env('CHAOS_SEED', 20260307);
        $productsCount = (int) env('CHAOS_PRODUCTS_COUNT', 250);

        if (! $reset) {
            $this->command?->warn('CHAOS_RESET=0 -> foundation seeder dibatalkan agar tidak mencampur data lama.');
            return;
        }

        $this->command?->warn('CHAOS foundation reset dimulai...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        DB::statement('TRUNCATE TABLE supplier_invoice_media');
        DB::statement('TRUNCATE TABLE supplier_invoice_items');
        DB::statement('TRUNCATE TABLE customer_transaction_lines');
        DB::statement('TRUNCATE TABLE employee_loan_payments');
        DB::statement('TRUNCATE TABLE inventory_movements');

        DB::statement('TRUNCATE TABLE supplier_invoices');
        DB::statement('TRUNCATE TABLE customer_transactions');
        DB::statement('TRUNCATE TABLE employee_loans');
        DB::statement('TRUNCATE TABLE salaries');
        DB::statement('TRUNCATE TABLE operational_expenses');
        DB::statement('TRUNCATE TABLE employees');

        DB::statement('TRUNCATE TABLE product_inventory');
        DB::statement('TRUNCATE TABLE products');

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->command?->info('Operational tables di-reset.');

        $this->call([
            UserSeeder::class,
        ]);

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        $brands = ['Aspira', 'Federal', 'Yamaha', 'Honda', 'Suzuki', 'Kawasaki', 'NGK', 'KTC', 'Nippon'];
        $sizes  = ['S', 'M', 'L', 'XL', '100ml', '250ml', '500ml', '1L'];
        $grades = ['Ori', 'KW', 'Premium'];

        for ($i = 1; $i <= $productsCount; $i++) {
            $code = sprintf('PRD-%04d', $i);

            // Tidak pakai faker->unique()->word() agar aman untuk jumlah besar.
            // Keunikan nama dijamin oleh suffix indeks.
            $baseWord = $faker->word();
            $name = sprintf(
                'Sparepart %s %s %03d',
                ucfirst($baseWord),
                $faker->randomElement($grades),
                $i
            );

            Product::query()->create([
                'code'       => $code,
                'name'       => $name,
                'brand'      => $faker->randomElement($brands),
                'size'       => $faker->randomElement($sizes),
                'sale_price' => (int) (round($faker->numberBetween(5000, 250000) / 1000) * 1000),
                'is_active'  => true,
            ]);
        }

        $this->command?->info("Foundation selesai. Products seeded: {$productsCount}, seed: {$seed}");
    }
}
