<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class AdjustEmployeeDebtFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_store_employee_debt_principal_adjustment(): void
    {
        [$employeeId, $debtId] = $this->seedDebt();

        $response = $this->actingAs($this->user('admin-debt-adjust@example.test', 'admin'))
            ->post(route('admin.employee-debts.adjustments.store', ['debtId' => $debtId]), [
                'adjustment_type' => 'increase',
                'amount' => 100000,
                'reason' => 'Tambahan hutang operasional',
            ]);

        $response->assertRedirect(route('admin.employee-debts.show', ['debtId' => $debtId]));
        $response->assertSessionHas('success', 'Penambahan hutang berhasil dicatat.');

        $this->assertDatabaseHas('employee_debt_adjustments', [
            'employee_debt_id' => $debtId,
            'adjustment_type' => 'increase',
            'amount' => 100000,
            'reason' => 'Tambahan hutang operasional',
        ]);

        $this->assertDatabaseHas('employee_debts', [
            'id' => $debtId,
            'total_debt' => 1100000,
            'remaining_balance' => 1100000,
            'status' => 'unpaid',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'employee_debt_principal_adjusted',
        ]);
    }

    public function test_admin_can_see_employee_debt_adjustment_history_on_detail_page(): void
    {
        [$employeeId, $debtId] = $this->seedDebt();

        DB::table('employee_debt_adjustments')->insert([
            'id' => (string) Str::uuid(),
            'employee_debt_id' => $debtId,
            'adjustment_type' => 'increase',
            'amount' => 100000,
            'reason' => 'Tambahan hutang operasional',
            'performed_by_actor_id' => '1',
            'before_total_debt' => 1000000,
            'after_total_debt' => 1100000,
            'before_remaining_balance' => 1000000,
            'after_remaining_balance' => 1100000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user('admin-debt-adjust-detail@example.test', 'admin'))
            ->get(route('admin.employee-debts.show', ['debtId' => $debtId]));

        $response->assertOk();
        $response->assertSee('Riwayat Koreksi Hutang');
        $response->assertSee('Tambah Hutang');
        $response->assertSee('Tambahan hutang operasional');
        $response->assertSee('Rp100.000');
    }

    private function seedDebt(): array
    {
        $employeeId = (string) Str::uuid();
        $debtId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Asyraf Koreksi Hutang',
            'phone' => '081111111111',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_debts')->insert([
            'id' => $debtId,
            'employee_id' => $employeeId,
            'total_debt' => 1000000,
            'remaining_balance' => 1000000,
            'status' => 'unpaid',
            'notes' => 'Kasbon awal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$employeeId, $debtId];
    }

    private function user(string $email, string $role): User
    {
        $user = User::query()->create(['name' => 'Test User', 'email' => $email, 'password' => 'password123']);
        DB::table('actor_accesses')->insert(['actor_id' => (string) $user->getAuthIdentifier(), 'role' => $role]);
        return $user;
    }
}
