<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Set kredensial admin dari ENV agar tidak "ngarang" di codebase.
        // Isi di .env:
        // ADMIN_USERNAME=...
        // ADMIN_PASSWORD=...
        $adminUsername = env('ADMIN_USERNAME');
        $adminPassword = env('ADMIN_PASSWORD');

        if (!$adminUsername || !$adminPassword) {
            throw new \RuntimeException('ADMIN_USERNAME / ADMIN_PASSWORD wajib di-set di .env sebelum seeding.');
        }

        User::query()->updateOrCreate(
            ['username' => $adminUsername],
            [
                'password_hash' => Hash::make($adminPassword),
                'role'          => 'admin',
                'is_active'     => true,
            ]
        );

        // Cashier ada row tapi tidak punya password (tidak bisa login web)
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
