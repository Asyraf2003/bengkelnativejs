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
        // 1. Seeder Wajib
        $this->call([
            UserSeeder::class,
        ]);

        // 2. Audit: Cek config, bukan env langsung. 
        // Menggunakan type casting (bool) untuk kepastian data.
        if ((bool) config('app.allow_dummy')) {
            $this->command->warn("⚠️ Menjalankan Seeder Data Dummy...");
        
            if (env('DUMMY_PRODUCTS', '0') === '1') { 
                $this->command->warn("⚠️ Menjalankan Seeder Data Dummy...");
                    $this->call([
                        DummyProductsSeeder::class,
                        DummyPhase4Seeder::class,
                    ]);
                }

            $this->command->info("✅ Data Dummy berhasil dimasukkan.");
        }
    }
}
