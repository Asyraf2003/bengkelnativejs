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

class ChaosPhase4Seeder extends Seeder
{
    public function run(): void
    {
        $seed = (int) env('CHAOS_SEED', 20260307) + 401;

        $employeesCount = (int) env('CHAOS_EMPLOYEES_COUNT', 18);
        $expensesMin = (int) env('CHAOS_OPER_EXPENSES_MIN', 180);
        $expensesMax = (int) env('CHAOS_OPER_EXPENSES_MAX', 320);
        $salariesMin = (int) env('CHAOS_SALARIES_MIN', 40);
        $salariesMax = (int) env('CHAOS_SALARIES_MAX', 90);
        $loansMin = (int) env('CHAOS_LOANS_MIN', 40);
        $loansMax = (int) env('CHAOS_LOANS_MAX', 80);
        $paymentsMin = (int) env('CHAOS_LOAN_PAYMENTS_MIN', 120);
        $paymentsMax = (int) env('CHAOS_LOAN_PAYMENTS_MAX', 240);
        $rangeDays = (int) env('CHAOS_RANGE_DAYS', 150);

        $faker = Faker::create('id_ID');
        $faker->seed($seed);

        $today = CarbonImmutable::today();
        $start = $today->subDays($rangeDays);

        $expenseCount = $faker->numberBetween($expensesMin, $expensesMax);
        $salaryCount = $faker->numberBetween($salariesMin, $salariesMax);
        $loanCount = $faker->numberBetween($loansMin, $loansMax);
        $paymentCount = $faker->numberBetween($paymentsMin, $paymentsMax);

        $expenseNames = [
            'Listrik',
            'Air',
            'Internet',
            'ATK',
            'Bensin Operasional',
            'Konsumsi',
            'Parkir',
            'Kebersihan',
            'Perawatan Toko',
            'Transport Kurir',
            'Servis Alat',
            'Keperluan Bengkel',
        ];

        $employeeIds = [];

        DB::transaction(function () use (
            $faker,
            $today,
            $start,
            $employeesCount,
            $expenseCount,
            $salaryCount,
            $loanCount,
            $paymentCount,
            $expenseNames,
            &$employeeIds
        ) {
            for ($i = 1; $i <= $employeesCount; $i++) {
                $employee = Employee::query()->create([
                    'name' => sprintf('Karyawan Chaos %02d', $i),
                ]);

                $employeeIds[] = (int) $employee->id;
            }

            for ($i = 1; $i <= $expenseCount; $i++) {
                $spentAt = CarbonImmutable::instance(
                    $faker->dateTimeBetween($start, $today)
                )->toDateString();

                $amount = (int) (round($faker->numberBetween(20_000, 1_500_000) / 1000) * 1000);

                OperationalExpense::query()->create([
                    'name' => $faker->randomElement($expenseNames),
                    'spent_at' => $spentAt,
                    'amount' => $amount,
                    'note' => sprintf('chaos-operational-%03d', $i),
                ]);
            }

            for ($i = 1; $i <= $salaryCount; $i++) {
                $paidAt = CarbonImmutable::instance(
                    $faker->dateTimeBetween($start, $today)
                )->toDateString();

                $amount = (int) (round($faker->numberBetween(500_000, 4_000_000) / 1000) * 1000);

                Salary::query()->create([
                    'employee_id' => (int) $faker->randomElement($employeeIds),
                    'paid_at' => $paidAt,
                    'amount' => $amount,
                    'note' => sprintf('chaos-salary-%03d', $i),
                ]);
            }

            $loanBuckets = [];

            for ($i = 1; $i <= $loanCount; $i++) {
                $loanedAt = CarbonImmutable::instance(
                    $faker->dateTimeBetween($start, $today)
                )->toDateString();

                $amount = (int) (round($faker->numberBetween(100_000, 3_500_000) / 1000) * 1000);

                $loan = EmployeeLoan::query()->create([
                    'employee_id' => (int) $faker->randomElement($employeeIds),
                    'loaned_at' => $loanedAt,
                    'amount' => $amount,
                    'note' => sprintf('chaos-loan-%03d', $i),
                ]);

                $loanBuckets[] = [
                    'id' => (int) $loan->id,
                    'remaining' => $amount,
                    'loaned_at' => $loanedAt,
                ];
            }

            for ($i = 1; $i <= $paymentCount; $i++) {
                if (count($loanBuckets) < 1) {
                    break;
                }

                $eligibleIndexes = array_keys(array_filter($loanBuckets, fn ($loan) => (int) $loan['remaining'] > 0));
                if (count($eligibleIndexes) < 1) {
                    break;
                }

                $pickIndex = (int) $faker->randomElement($eligibleIndexes);
                $bucket = $loanBuckets[$pickIndex];

                $remaining = (int) $bucket['remaining'];
                if ($remaining <= 0) {
                    continue;
                }

                $loanedAt = CarbonImmutable::parse((string) $bucket['loaned_at'])->startOfDay();
                $paymentDateMax = $today->greaterThan($loanedAt) ? $today : $loanedAt;

                $paidAt = CarbonImmutable::instance(
                    $faker->dateTimeBetween($loanedAt, $paymentDateMax)
                )->toDateString();

                $rawAmount = (int) max(
                    10_000,
                    (int) round($remaining * $faker->randomFloat(2, 0.10, 0.65))
                );

                $amount = (int) (round($rawAmount / 1000) * 1000);
                if ($amount > $remaining) {
                    $amount = $remaining;
                }
                if ($amount <= 0) {
                    continue;
                }

                EmployeeLoanPayment::query()->create([
                    'employee_loan_id' => (int) $bucket['id'],
                    'paid_at' => $paidAt,
                    'amount' => $amount,
                    'note' => sprintf('chaos-loan-payment-%03d', $i),
                ]);

                $loanBuckets[$pickIndex]['remaining'] = $remaining - $amount;
            }
        });

        $this->command?->info("Chaos employees created: {$employeesCount}");
        $this->command?->info("Chaos operational expenses created: {$expenseCount}");
        $this->command?->info("Chaos salaries created: {$salaryCount}");
        $this->command?->info("Chaos loans created: {$loanCount}");
        $this->command?->info("Chaos loan payments created: {$paymentCount}");
    }
}
