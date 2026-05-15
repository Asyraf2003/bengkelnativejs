<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ReversePayrollDisbursementFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reverse_payroll_disbursement(): void
    {
        $employeeId = (string) Str::uuid();
        $payrollId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Budi Payroll',
            'phone' => '0812',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payroll_disbursements')->insert([
            'id' => $payrollId,
            'employee_id' => $employeeId,
            'amount' => 5000000,
            'disbursement_date' => '2026-03-25 00:00:00',
            'mode' => 'monthly',
            'notes' => 'Gaji Maret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user('admin-payroll-reverse@example.test', 'admin'))
            ->post(route('admin.payrolls.reverse.store', ['payrollId' => $payrollId]), [
                'reason' => 'Koreksi payout payroll',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Pencairan gaji berhasil dibatalkan.');

        $this->assertDatabaseHas('payroll_disbursement_reversals', [
            'payroll_disbursement_id' => $payrollId,
            'reason' => 'Koreksi payout payroll',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'payroll_disbursement_reversed',
        ]);
    }

    public function test_reversed_payroll_is_excluded_from_employee_payroll_summary_and_still_visible_in_payroll_table_endpoint(): void
    {
        $employeeId = (string) Str::uuid();
        $payrollId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Budi Payroll',
            'phone' => '0812',
            'default_salary_amount' => 5000000,
            'salary_basis_type' => 'monthly',
            'employment_status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payroll_disbursements')->insert([
            'id' => $payrollId,
            'employee_id' => $employeeId,
            'amount' => 5000000,
            'disbursement_date' => '2026-03-25 00:00:00',
            'mode' => 'monthly',
            'notes' => 'Gaji Maret',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('payroll_disbursement_reversals')->insert([
            'id' => (string) Str::uuid(),
            'payroll_disbursement_id' => $payrollId,
            'reason' => 'Koreksi payout payroll',
            'performed_by_actor_id' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $admin = $this->user('admin-payroll-summary@example.test', 'admin');

        $response = $this->actingAs($admin)
            ->get(route('admin.employees.show', ['employeeId' => $employeeId]));

        $response->assertOk();
        $response->assertSee('Detail Karyawan');
        $response->assertSee('Ringkasan Karyawan');
        $response->assertSee('Riwayat Versi Karyawan');
        $response->assertSee('Edit Karyawan');
        $response->assertDontSee('Lihat Hutang Karyawan');

        $tableResponse = $this->actingAs($admin)->getJson(route('admin.employees.payroll-table', [
            'employeeId' => $employeeId,
            'page' => 1,
            'per_page' => 10,
        ]));

        $tableResponse->assertOk();
        $tableResponse->assertJsonPath('success', true);
        $tableResponse->assertJsonPath('data.meta.total', 1);
        $tableResponse->assertJsonFragment(['notes' => 'Gaji Maret']);
        $tableResponse->assertJsonFragment(['is_reversed' => true]);
        $tableResponse->assertJsonFragment(['reversal_reason' => 'Koreksi payout payroll']);
        $tableResponse->assertJsonFragment(['mode_label' => 'Bulanan']);
    }

    private function user(string $email, string $role): User
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