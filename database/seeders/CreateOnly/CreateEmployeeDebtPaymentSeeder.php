<?php

declare(strict_types=1);

namespace Database\Seeders\CreateOnly;

use Database\Seeders\CreateOnly\Support\CreateOnlySeeder;
use Database\Seeders\CreateOnly\Support\CreateOnlySeedCalendar;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CreateEmployeeDebtPaymentSeeder extends CreateOnlySeeder
{
    private const DEBT_TABLE = 'employee_debts';
    private const PAYMENT_TABLE = 'employee_debt_payments';

    /**
     * @var list<array{
     *   id:string,
     *   employee_index:int,
     *   total_debt:int,
     *   remaining_balance:int,
     *   status:string,
     *   notes:string,
     *   day:int,
     *   time:string,
     *   payments:list<array{
     *     id:string,
     *     amount:int,
     *     day:int,
     *     time:string,
     *     notes:string,
     *   }>
     * }>
     */
    private const DEBT_SCENARIOS = [
        [
            'id' => '00000000-0000-5000-0002-000000000001',
            'employee_index' => 6,
            'total_debt' => 500000,
            'remaining_balance' => 300000,
            'status' => 'unpaid',
            'notes' => 'Seed kasbon cicilan sebagian - ban dalam',
            'day' => 20,
            'time' => '10:10:00',
            'payments' => [
                [
                    'id' => '00000000-0000-5100-0002-000000000001',
                    'amount' => 200000,
                    'day' => 20,
                    'time' => '11:10:00',
                    'notes' => 'Seed pembayaran kasbon sebagian',
                ],
            ],
        ],
        [
            'id' => '00000000-0000-5000-0002-000000000002',
            'employee_index' => 7,
            'total_debt' => 800000,
            'remaining_balance' => 0,
            'status' => 'paid',
            'notes' => 'Seed kasbon lunas dua kali bayar',
            'day' => 20,
            'time' => '10:20:00',
            'payments' => [
                [
                    'id' => '00000000-0000-5100-0002-000000000002',
                    'amount' => 300000,
                    'day' => 20,
                    'time' => '11:20:00',
                    'notes' => 'Seed pembayaran kasbon lunas tahap 1',
                ],
                [
                    'id' => '00000000-0000-5100-0002-000000000003',
                    'amount' => 500000,
                    'day' => 20,
                    'time' => '12:20:00',
                    'notes' => 'Seed pembayaran kasbon lunas tahap 2',
                ],
            ],
        ],
        [
            'id' => '00000000-0000-5000-0002-000000000003',
            'employee_index' => 8,
            'total_debt' => 1200000,
            'remaining_balance' => 850000,
            'status' => 'unpaid',
            'notes' => 'Seed kasbon besar masih berjalan',
            'day' => 20,
            'time' => '10:30:00',
            'payments' => [
                [
                    'id' => '00000000-0000-5100-0002-000000000004',
                    'amount' => 150000,
                    'day' => 20,
                    'time' => '11:30:00',
                    'notes' => 'Seed pembayaran kasbon besar tahap 1',
                ],
                [
                    'id' => '00000000-0000-5100-0002-000000000005',
                    'amount' => 200000,
                    'day' => 20,
                    'time' => '12:30:00',
                    'notes' => 'Seed pembayaran kasbon besar tahap 2',
                ],
            ],
        ],
        [
            'id' => '00000000-0000-5000-0002-000000000004',
            'employee_index' => 9,
            'total_debt' => 250000,
            'remaining_balance' => 0,
            'status' => 'paid',
            'notes' => 'Seed kasbon kecil langsung lunas',
            'day' => 20,
            'time' => '10:40:00',
            'payments' => [
                [
                    'id' => '00000000-0000-5100-0002-000000000006',
                    'amount' => 250000,
                    'day' => 20,
                    'time' => '11:40:00',
                    'notes' => 'Seed pembayaran kasbon kecil lunas',
                ],
            ],
        ],
    ];

    public function run(): void
    {
        $this->assertLocalOrTesting();

        $employeeIds = $this->employeeIds();

        $createdDebts = 0;
        $createdPayments = 0;

        foreach (self::DEBT_SCENARIOS as $scenario) {
            $this->assertScenarioIsBalanced($scenario);

            $employeeId = $employeeIds[$scenario['employee_index']] ?? null;

            if ($employeeId === null) {
                throw new RuntimeException('Not enough employees to seed employee debt payments.');
            }

            if ($this->debtExists($scenario['id'])) {
                $this->assertExistingDebtMatches($scenario['id'], $scenario);
            } else {
                $debtCreatedAt = $this->scenarioDateTime($scenario);

                if ($this->createOnly(self::DEBT_TABLE, 'id', $scenario['id'], [
                    'id' => $scenario['id'],
                    'employee_id' => $employeeId,
                    'total_debt' => $scenario['total_debt'],
                    'remaining_balance' => $scenario['remaining_balance'],
                    'status' => $scenario['status'],
                    'notes' => $scenario['notes'],
                    'created_at' => $debtCreatedAt,
                    'updated_at' => $debtCreatedAt,
                ])) {
                    $createdDebts++;
                }
            }

            foreach ($scenario['payments'] as $payment) {
                if ($this->paymentExists($payment['id'])) {
                    $this->assertExistingPaymentMatches($payment['id'], $scenario['id'], $payment);
                    continue;
                }

                $paymentDate = $this->scenarioDateTime($payment);

                if ($this->createOnly(self::PAYMENT_TABLE, 'id', $payment['id'], [
                    'id' => $payment['id'],
                    'employee_debt_id' => $scenario['id'],
                    'amount' => $payment['amount'],
                    'payment_date' => $paymentDate,
                    'notes' => $payment['notes'],
                    'created_at' => $paymentDate,
                    'updated_at' => $paymentDate,
                ])) {
                    $createdPayments++;
                }
            }
        }

        $this->command?->info(sprintf(
            'create-only employee debt payments: planned_debts=%d created_debts=%d planned_payments=%d created_payments=%d',
            count(self::DEBT_SCENARIOS),
            $createdDebts,
            $this->plannedPaymentCount(),
            $createdPayments
        ));
    }

    /**
     * @return list<string>
     */
    private function employeeIds(): array
    {
        return DB::table('employees')
            ->orderBy('id')
            ->limit(20)
            ->pluck('id')
            ->map(static fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    /**
     * @param array<string, mixed> $scenario
     */
    private function assertScenarioIsBalanced(array $scenario): void
    {
        $paid = array_sum(array_map(
            static fn (array $payment): int => (int) $payment['amount'],
            $scenario['payments']
        ));

        $expectedRemaining = (int) $scenario['total_debt'] - $paid;

        if ($expectedRemaining !== (int) $scenario['remaining_balance']) {
            throw new RuntimeException(sprintf('Seed debt scenario is not balanced: %s.', $scenario['id']));
        }

        $expectedStatus = $expectedRemaining === 0 ? 'paid' : 'unpaid';

        if ($expectedStatus !== (string) $scenario['status']) {
            throw new RuntimeException(sprintf('Seed debt scenario status mismatch: %s.', $scenario['id']));
        }
    }

    /**
     * @param array<string, mixed> $scenario
     */
    private function scenarioDateTime(array $scenario): string
    {
        return CreateOnlySeedCalendar::currentMonthDate((int) $scenario['day'])
            .' '
            .(string) $scenario['time'];
    }


    private function debtExists(string $id): bool
    {
        return DB::table(self::DEBT_TABLE)
            ->where('id', $id)
            ->exists();
    }

    private function paymentExists(string $id): bool
    {
        return DB::table(self::PAYMENT_TABLE)
            ->where('id', $id)
            ->exists();
    }

    /**
     * @param array<string, mixed> $scenario
     */
    private function assertExistingDebtMatches(string $id, array $scenario): void
    {
        $row = DB::table(self::DEBT_TABLE)
            ->where('id', $id)
            ->first();

        if ($row === null) {
            throw new RuntimeException(sprintf('Expected existing debt missing: %s.', $id));
        }

        $expected = [
            'total_debt' => (int) $scenario['total_debt'],
            'remaining_balance' => (int) $scenario['remaining_balance'],
            'status' => (string) $scenario['status'],
        ];

        $actual = [
            'total_debt' => (int) $row->total_debt,
            'remaining_balance' => (int) $row->remaining_balance,
            'status' => (string) $row->status,
        ];

        if ($actual !== $expected) {
            throw new RuntimeException(sprintf('Existing debt differs from seed contract: %s.', $id));
        }
    }

    /**
     * @param array<string, mixed> $payment
     */
    private function assertExistingPaymentMatches(string $id, string $debtId, array $payment): void
    {
        $row = DB::table(self::PAYMENT_TABLE)
            ->where('id', $id)
            ->first();

        if ($row === null) {
            throw new RuntimeException(sprintf('Expected existing payment missing: %s.', $id));
        }

        $expected = [
            'employee_debt_id' => $debtId,
            'amount' => (int) $payment['amount'],
        ];

        $actual = [
            'employee_debt_id' => (string) $row->employee_debt_id,
            'amount' => (int) $row->amount,
        ];

        if ($actual !== $expected) {
            throw new RuntimeException(sprintf('Existing payment differs from seed contract: %s.', $id));
        }
    }

    private function plannedPaymentCount(): int
    {
        return array_sum(array_map(
            static fn (array $scenario): int => count($scenario['payments']),
            self::DEBT_SCENARIOS
        ));
    }

}
