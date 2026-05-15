<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class WebPageAccessFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_admin_dashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_accessing_cashier_dashboard(): void
    {
        $response = $this->get(route('cashier.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = $this->createUserWithRole('admin-access@example.test', 'admin');

        $response = $this
            ->actingAs($user)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Dashboard Laporan');
    }

    public function test_kasir_can_access_cashier_dashboard(): void
    {
        $user = $this->createUserWithRole('kasir-access@example.test', 'kasir');

        $response = $this
            ->actingAs($user)
            ->get(route('cashier.dashboard'));

        $response->assertOk();
        $response->assertSee('Buat Nota Baru');
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_admin_dashboard(): void
    {
        $user = $this->createUserWithRole('kasir-denied-admin@example.test', 'kasir');

        $response = $this
            ->actingAs($user)
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_without_cashier_area_capability_is_redirected_back_to_admin_dashboard(): void
    {
        $user = $this->createUserWithRole('admin-no-cashier@example.test', 'admin');

        $response = $this
            ->actingAs($user)
            ->get(route('cashier.dashboard'));

        $response->assertRedirect(route('admin.dashboard'));
        $response->assertSessionHas('error', 'Admin belum diizinkan mengakses area kasir.');
    }

    public function test_admin_with_cashier_area_capability_can_access_cashier_dashboard(): void
    {
        $user = $this->createUserWithRole('admin-cashier-access@example.test', 'admin');

        DB::table('admin_cashier_area_access_states')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'active' => true,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('cashier.dashboard'));

        $response->assertOk();
        $response->assertSee('Buat Nota Baru');
    }

    public function test_authenticated_user_without_actor_access_is_redirected_to_login_from_admin_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Missing Actor Access',
            'email' => 'missing-actor-access@example.test',
            'password' => 'password123',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('admin.dashboard'));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', 'Aktor tidak dikenali.');

        $this->assertGuest();
    }

    private function createUserWithRole(string $email, string $role): User
    {
        $user = User::query()->create([
            'name' => 'Test User',
            'email' => $email,
            'password' => 'password123',
        ]);

        DB::table('actor_accesses')->insert([
            'actor_id' => (string) $user->getAuthIdentifier(),
            'role' => $role,
        ]);

        return $user;
    }
}