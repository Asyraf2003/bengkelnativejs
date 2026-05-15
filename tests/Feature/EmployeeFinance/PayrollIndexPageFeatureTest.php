<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class PayrollIndexPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_when_accessing_payroll_index_page(): void
    {
        $this->get(route('admin.payrolls.index'))->assertRedirect(route('login'));
    }

    public function test_kasir_is_redirected_back_to_cashier_dashboard_when_accessing_payroll_index_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('kasir-payroll-index@example.test', 'kasir'))
            ->get(route('admin.payrolls.index'));

        $response->assertRedirect(route('cashier.dashboard'));
        $response->assertSessionHas('error', 'Halaman admin hanya untuk role admin.');
    }

    public function test_admin_can_access_payroll_index_shell_page(): void
    {
        $response = $this->actingAs($this->createUserWithRole('admin-payroll-index@example.test', 'admin'))
            ->get(route('admin.payrolls.index'));

        $response->assertOk();
        $response->assertSee('Tabel riwayat pencairan gaji manual');
        $response->assertSee('payroll-search-form', false);
        $response->assertSee('payroll-table-body', false);
        $response->assertSee('admin-payrolls-table.js');
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
