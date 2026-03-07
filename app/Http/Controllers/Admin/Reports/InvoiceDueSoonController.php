<?php

namespace App\Http\Controllers\Admin\Reports;

use App\Application\UseCases\Reports\BuildInvoiceDueSoonReportUseCase;

class InvoiceDueSoonController
{
    public function __invoke(BuildInvoiceDueSoonReportUseCase $useCase)
    {
        $report = $useCase->execute([
            'days' => 5,
            'per_page' => 20,
        ]);

        $today = $report['today'];
        $until = $report['until'];
        $rows = $report['rows'];

        return view('admin.reports.invoice_due_soon', compact('today', 'until', 'rows'));
    }
}
