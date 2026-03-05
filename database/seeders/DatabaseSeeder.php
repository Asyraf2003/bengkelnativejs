<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seeder Wajib (Selalu dijalankan)
        $this->call([
            UserSeeder::class,
        ]);

        // 2. Seeder Opsional (Hanya jika DUMMY_PRODUCTS=1 di .env)
        // Audit: Menggunakan env() dengan default '0' untuk keamanan data
        if (env('DUMMY_PRODUCTS', '0') === '1') {
            $this->call(
                DummyProductsSeeder::class,
                DummyPhase4Seeder::class
            );
        }
    }
}
