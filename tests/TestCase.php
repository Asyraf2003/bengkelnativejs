<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Memenuhi Kontrak Identity & Access (Blueprint 1.3.1)
     * Menciptakan User Laravel + Actor Access Domain dengan Role Kasir.
     */
    protected function loginAsKasir(): User
    {
        $user = User::factory()->create();

        DB::table('actor_accesses')->insert([
            'actor_id' => $user->getAuthIdentifier(),
            'role' => 'kasir', // Nilai sesuai kontrak domain Role::KASIR
        ]);

        $this->actingAs($user);

        return $user;
    }

    /**
     * Menciptakan Admin yang memiliki kapabilitas transaksi (Blueprint 1.3.1 - Keputusan aktif v1)
     */
    protected function loginAsAuthorizedAdmin(): User
    {
        $user = User::factory()->create();

        DB::table('actor_accesses')->insert([
            'actor_id' => $user->getAuthIdentifier(),
            'role' => 'admin',
        ]);

        // Admin butuh record di capability states agar isInactive() bernilai false
        DB::table('admin_transaction_capability_states')->insert([
            'actor_id' => $user->getAuthIdentifier(),
            'is_active' => true,
            'capability_key' => 'transaction_entry',
            'updated_at' => now(),
        ]);

        $this->actingAs($user);

        return $user;
    }
}
