<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use App\Core\IdentityAccess\Role\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

final class CreateUserSeeder extends Seeder
{
    private const DEFAULT_LOCAL_PASSWORD = '12345678';

    public function run(): void
    {
        $this->assertLocalOrTesting();

        $adminId = $this->createUserOnly(
            name: 'Admin Demo',
            email: 'admin@gmail.com',
            password: self::DEFAULT_LOCAL_PASSWORD,
        );

        $kasirId = $this->createUserOnly(
            name: 'Kasir Demo',
            email: 'kasir@gmail.com',
            password: self::DEFAULT_LOCAL_PASSWORD,
        );

        $this->createActorAccessOnly((string) $adminId, Role::ADMIN);
        $this->createActorAccessOnly((string) $kasirId, Role::KASIR);
        $this->createAdminCapabilityOnly((string) $adminId);
        $this->createAdminCashierAreaAccessOnly((string) $adminId);
    }

    private function assertLocalOrTesting(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException(self::class.' is only allowed in local/testing environments.');
        }
    }

    private function createUserOnly(string $name, string $email, string $password): int
    {
        $existing = DB::table('users')
            ->where('email', '=', $email)
            ->first(['id']);

        if ($existing !== null) {
            return (int) $existing->id;
        }

        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createActorAccessOnly(string $actorId, string $role): void
    {
        if (DB::table('actor_accesses')->where('actor_id', '=', $actorId)->exists()) {
            return;
        }

        DB::table('actor_accesses')->insert([
            'actor_id' => $actorId,
            'role' => $role,
        ]);
    }

    private function createAdminCapabilityOnly(string $actorId): void
    {
        if (DB::table('admin_transaction_capability_states')->where('actor_id', '=', $actorId)->exists()) {
            return;
        }

        DB::table('admin_transaction_capability_states')->insert([
            'actor_id' => $actorId,
            'active' => true,
        ]);
    }

    private function createAdminCashierAreaAccessOnly(string $actorId): void
    {
        if (DB::table('admin_cashier_area_access_states')->where('actor_id', '=', $actorId)->exists()) {
            return;
        }

        DB::table('admin_cashier_area_access_states')->insert([
            'actor_id' => $actorId,
            'active' => true,
        ]);
    }
}
