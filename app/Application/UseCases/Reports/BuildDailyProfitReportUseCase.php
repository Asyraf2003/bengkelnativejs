<?php

namespace App\Application\UseCases\Reports;

use App\Models\CustomerTransaction;
use App\Models\CustomerTransactionLine;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanPayment;
use App\Models\OperationalExpense;
use App\Models\Salary;
use Carbon\CarbonImmutable;

class BuildDailyProfitReportUseCase
{
    /**
     * @param array{date:string} $input
     * @return array{
     *   date:string,
     *   cash_in: array{
     *     transaction_revenue:int,
     *     employee_loan_payments:int,
     *     total:int
     *   },
     *   cash_out: array{
     *     refunds:int,
     *     operational_expenses:int,
     *     salaries:int,
     *     employee_loans:int,
     *     outside_cost:int,
     *     total:int
     *   },
     *   cogs:int,
     *   profit:int
     * }
     */
    public function execute(array $input): array
    {
        $date = CarbonImmutable::parse($input['date'])->toDateString();

        $transactionRevenue = (int) CustomerTransactionLine::query()
            ->selectRaw('COALESCE(SUM(customer_transaction_lines.amount), 0) as total')
            ->join(
                'customer_transactions',
                'customer_transactions.id',
                '=',
                'customer_transaction_lines.customer_transaction_id'
            )
            ->whereDate('customer_transactions.paid_at', $date)
            ->whereIn('customer_transaction_lines.kind', [
                'product_sale',
                'service_fee',
                'service_product',
            ])
            ->value('total');

        $employeeLoanPayments = (int) EmployeeLoanPayment::query()
            ->whereDate('paid_at', $date)
            ->sum('amount');

        $refunds = (int) CustomerTransaction::query()
            ->whereDate('refunded_at', $date)
            ->sum('refund_amount');

        $operationalExpenses = (int) OperationalExpense::query()
            ->whereDate('spent_at', $date)
            ->sum('amount');

        $salaries = (int) Salary::query()
            ->whereDate('paid_at', $date)
            ->sum('amount');

        $employeeLoans = (int) EmployeeLoan::query()
            ->whereDate('loaned_at', $date)
            ->sum('amount');

        $outsideCost = (int) CustomerTransactionLine::query()
            ->selectRaw('COALESCE(SUM(customer_transaction_lines.amount), 0) as total')
            ->join(
                'customer_transactions',
                'customer_transactions.id',
                '=',
                'customer_transaction_lines.customer_transaction_id'
            )
            ->whereDate('customer_transactions.paid_at', $date)
            ->where('customer_transaction_lines.kind', 'outside_cost')
            ->value('total');

        $cogs = (int) CustomerTransactionLine::query()
            ->selectRaw('COALESCE(SUM(customer_transaction_lines.cogs_amount), 0) as total')
            ->join(
                'customer_transactions',
                'customer_transactions.id',
                '=',
                'customer_transaction_lines.customer_transaction_id'
            )
            ->whereDate('customer_transactions.paid_at', $date)
            ->whereIn('customer_transaction_lines.kind', [
                'product_sale',
                'service_product',
            ])
            ->value('total');

        $cashInTotal = $transactionRevenue + $employeeLoanPayments;
        $cashOutTotal = $refunds + $operationalExpenses + $salaries + $employeeLoans + $outsideCost;
        $profit = $cashInTotal - $cashOutTotal - $cogs;

        return [
            'date' => $date,
            'cash_in' => [
                'transaction_revenue' => $transactionRevenue,
                'employee_loan_payments' => $employeeLoanPayments,
                'total' => $cashInTotal,
            ],
            'cash_out' => [
                'refunds' => $refunds,
                'operational_expenses' => $operationalExpenses,
                'salaries' => $salaries,
                'employee_loans' => $employeeLoans,
                'outside_cost' => $outsideCost,
                'total' => $cashOutTotal,
            ],
            'cogs' => $cogs,
            'profit' => $profit,
        ];
    }
}
