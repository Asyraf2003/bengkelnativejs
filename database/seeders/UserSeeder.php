<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $adminUsername = env('ADMIN_USERNAME');
        $adminPassword = env('ADMIN_PASSWORD');

        // Audit check: Validasi keberadaan data di .env
        if (!$adminUsername || !$adminPassword) {
            throw new \RuntimeException('ADMIN_USERNAME / ADMIN_PASSWORD wajib di-set di .env sebelum seeding.');
        }

        // 1. Create/Update Admin
        User::query()->updateOrCreate(
            ['username' => $adminUsername],
            [
                'password_hash' => Hash::make($adminPassword),
                'role'          => 'admin',
                'is_active'     => true,
            ]
        );

        // 2. Create/Update Cashier (Tanpa password sesuai request)
        User::query()->updateOrCreate(
            ['username' => 'cashier'],
            [
                'password_hash' => null,
                'role'          => 'cashier',
                'is_active'     => true,
            ]
        );
    }
}
