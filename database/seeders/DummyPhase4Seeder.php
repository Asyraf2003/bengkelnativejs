<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\OperationalExpense;
use App\Models\Salary;
use Carbon\CarbonImmutable;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyPhase4Seeder extends Seeder
{
    public function run(): void
    {
        $seed = (int) env('DUMMY_SEED', 20260305);

        $employeesCount = (int) env('DUMMY_EMPLOYEES', 5);
        $expensesCount  = (int) env('DUMMY_OPER_EXPENSES', 30);
        $salariesCount  = (int) env('DUMMY_SALARIES', 10);
        $loansCount     = (int) env('DUMMY_LOANS', 10);
        $paymentsCount  = (int) env('DUMMY_LOAN_PAYMENTS', 25);

        $rangeDays      = (int) env('DUMMY_RANGE_DAYS', 120);
        $reset          = env('DUMMY_PHASE4_RESET', '0') === '1';

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        $today = CarbonImmutable::today();
        $start = $today->subDays($rangeDays);

        // ✅ RESET HARUS DI LUAR TRANSACTION (TRUNCATE implicit commit di MySQL)
        if ($reset) {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');

            DB::statement('TRUNCATE TABLE employee_loan_payments');
            DB::statement('TRUNCATE TABLE employee_loans');
            DB::statement('TRUNCATE TABLE salaries');
            DB::statement('TRUNCATE TABLE operational_expenses');
            DB::statement('TRUNCATE TABLE employees');

            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        // ✅ INSERT DATA BARU AMAN DI TRANSACTION
        DB::transaction(function () use (
            $employeesCount,
            $expensesCount,
            $salariesCount,
            $loansCount,
            $paymentsCount,
            $faker,
            $start,
            $today
        ) {
            // Employees
            $employeeIds = [];
            for ($i = 1; $i <= $employeesCount; $i++) {
                $e = Employee::query()->create([
                    'name' => sprintf('Karyawan %02d', $i),
                ]);
                $employeeIds[] = $e->id;
            }

            // Operational expenses
            $expenseNames = ['Bensin', 'Listrik', 'Air', 'ATK', 'Internet', 'Konsumsi', 'Parkir', 'Kebersihan'];
            for ($i = 0; $i < $expensesCount; $i++) {
                $spentAt = $faker->dateTimeBetween($start, $today)->format('Y-m-d');
                $amount = (int) (round($faker->numberBetween(10_000, 500_000) / 1000) * 1000);

                OperationalExpense::query()->create([
                    'name'     => $faker->randomElement($expenseNames),
                    'spent_at' => $spentAt,
                    'amount'   => $amount,
                    'note'     => 'dummy seeder',
                ]);
            }

            // Salaries
            for ($i = 0; $i < $salariesCount; $i++) {
                $paidAt = $faker->dateTimeBetween($start, $today)->format('Y-m-d');
                $amount = (int) (round($faker->numberBetween(500_000, 3_000_000) / 1000) * 1000);

                Salary::query()->create([
                    'employee_id' => (int) $faker->randomElement($employeeIds),
                    'paid_at'     => $paidAt,
                    'amount'      => $amount,
                    'note'        => 'dummy seeder',
                ]);
            }

            // Loans
            $loans = [];
            for ($i = 0; $i < $loansCount; $i++) {
                $loanedAt = $faker->dateTimeBetween($start, $today)->format('Y-m-d');
                $amount = (int) (round($faker->numberBetween(100_000, 2_000_000) / 1000) * 1000);

                $loan = EmployeeLoan::query()->create([
                    'employee_id' => (int) $faker->randomElement($employeeIds),
                    'loaned_at'   => $loanedAt,
                    'amount'      => $amount,
                    'note'        => 'dummy seeder',
                ]);

                $loans[] = [
                    'id' => $loan->id,
                    'remaining' => $amount,
                ];
            }

            // Loan payments (multi payment, tidak melebihi remaining)
            for ($i = 0; $i < $paymentsCount; $i++) {
                if (count($loans) === 0) break;

                $idx = (int) $faker->numberBetween(0, count($loans) - 1);
                $loanId = (int) $loans[$idx]['id'];
                $remaining = (int) $loans[$idx]['remaining'];

                if ($remaining <= 0) {
                    continue;
                }

                $paidAt = $faker->dateTimeBetween($start, $today)->format('Y-m-d');

                $raw = (int) max(10_000, (int) round($remaining * $faker->randomFloat(2, 0.10, 0.60)));
                $amount = (int) (round($raw / 1000) * 1000);
                if ($amount > $remaining) $amount = $remaining;

                EmployeeLoanPayment::query()->create([
                    'employee_loan_id' => $loanId,
                    'paid_at'          => $paidAt,
                    'amount'           => $amount,
                    'note'             => 'dummy seeder',
                ]);

                $loans[$idx]['remaining'] = $remaining - $amount;
            }
        });
    }
}
