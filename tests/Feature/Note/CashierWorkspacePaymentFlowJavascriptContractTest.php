<?php

declare(strict_types=1);

namespace Tests\Feature\Note;

use Tests\TestCase;

final class CashierWorkspacePaymentFlowJavascriptContractTest extends TestCase
{
    public function test_payment_flow_consumes_backend_payable_dataset_for_edit_calculator(): void
    {
        $script = (string) file_get_contents(base_path('public/assets/static/js/pages/cashier-note-workspace/payment-flow.js'));

        $this->assertStringContainsString(
            'dataset.backendPayableRupiah',
            $script,
            'Payment flow JS must read the backend payable dataset exposed by the edit payment modal.'
        );

        $this->assertStringContainsString(
            'dataset.backendPaymentBasis',
            $script,
            'Payment flow JS must read the backend payment basis dataset exposed by the edit payment modal.'
        );

        $this->assertStringContainsString(
            'backend_outstanding_settlement',
            $script,
            'Payment flow JS must only treat backend_outstanding_settlement as backend-owned payable truth.'
        );
    }
}
