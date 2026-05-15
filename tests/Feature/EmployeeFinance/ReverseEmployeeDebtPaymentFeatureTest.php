<?php

declare(strict_types=1);

namespace Tests\Feature\EmployeeFinance;

use App\Adapters\Out\Persistence\Eloquent\IdentityAccess\EloquentUser as User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ReverseEmployeeDebtPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_reverse_employee_debt_payment(): void
    {
        $employeeId = (string) Str::uuid();
        $debtId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Budi Hutang',
            'phone' => '0812',
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
            'remaining_balance' => 750000,
            'status' => 'unpaid',
            'notes' => 'Kasbon awal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_debt_payments')->insert([
            'id' => $paymentId,
            'employee_debt_id' => $debtId,
            'amount' => 250000,
            'payment_date' => '2026-04-11 10:00:00',
            'notes' => 'Cicilan pertama',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user('admin-debt-payment-reverse@example.test', 'admin'))
            ->from(route('admin.employee-debts.show', ['debtId' => $debtId]))
            ->post(route('admin.employee-debt-payments.reverse.store', ['paymentId' => $paymentId]), [
                'reason' => 'Salah input pembayaran',
            ]);

        $response->assertRedirect(route('admin.employee-debts.show', ['debtId' => $debtId]));
        $response->assertSessionHas('success', 'Reversal pembayaran hutang berhasil dicatat.');

        $this->assertDatabaseHas('employee_debt_payment_reversals', [
            'employee_debt_payment_id' => $paymentId,
            'reason' => 'Salah input pembayaran',
        ]);

        $this->assertDatabaseHas('employee_debts', [
            'id' => $debtId,
            'remaining_balance' => 1000000,
            'status' => 'unpaid',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'employee_debt_payment_reversed',
        ]);
    }

    public function test_reversed_employee_debt_payment_cannot_be_reversed_twice(): void
    {
        $employeeId = (string) Str::uuid();
        $debtId = (string) Str::uuid();
        $paymentId = (string) Str::uuid();

        DB::table('employees')->insert([
            'id' => $employeeId,
            'employee_name' => 'Budi Hutang',
            'phone' => '0812',
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
            'remaining_balance' => 750000,
            'status' => 'unpaid',
            'notes' => 'Kasbon awal',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_debt_payments')->insert([
            'id' => $paymentId,
            'employee_debt_id' => $debtId,
            'amount' => 250000,
            'payment_date' => '2026-04-11 10:00:00',
            'notes' => 'Cicilan pertama',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('employee_debt_payment_reversals')->insert([
            'id' => (string) Str::uuid(),
            'employee_debt_payment_id' => $paymentId,
            'reason' => 'Salah input pembayaran',
            'performed_by_actor_id' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->user('admin-debt-payment-reverse-twice@example.test', 'admin'))
            ->from(route('admin.employee-debts.show', ['debtId' => $debtId]))
            ->post(route('admin.employee-debt-payments.reverse.store', ['paymentId' => $paymentId]), [
                'reason' => 'Coba reversal lagi',
            ]);

        $response->assertRedirect(route('admin.employee-debts.show', ['debtId' => $debtId]));
        $response->assertSessionHasErrors([
            'debt_payment_reversal' => 'Pembayaran hutang ini sudah direversal.',
        ]);

        $this->assertDatabaseCount('employee_debt_payment_reversals', 1);
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
