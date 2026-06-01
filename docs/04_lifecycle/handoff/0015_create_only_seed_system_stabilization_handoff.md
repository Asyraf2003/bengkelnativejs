# CreateOnly Seed System Stabilization Handoff

## Status

Active handoff.

This handoff records CreateOnly seed system stabilization for manual QA and owner-readable reporting preparation.

This handoff does not close the full seed system.

## Source Of Truth

Local command output is the highest source of truth.

Do not claim a seed, report, projection, test, or verify status without local proof.

Do not discuss git workflow unless explicitly requested.

## Scope

### In Scope

- Stabilize human-facing create-all seed command.
- Ensure source seed rows appear in read-model projections.
- Move existing CreateOnly seed dates into the active month.
- Add a minimal weekly create transaction seed through the real create transaction use case.
- Prepare next report sanity step before scaling dataset.

### Out Of Scope For Current State

- Edit/revision seeding.
- Soft delete seeding.
- Refund seeding, except future disabled/pending scaffold.
- Monthly 100-200 juta dataset.
- Peak 500 juta/month dataset.
- Stress 6-8 miliar/month dataset.
- Report wording patch.
- Full lifecycle closure.

## Completed Work

### SEED-INFRA-001 - Human-facing create-all projection rebuild

Human-facing create-all-v3 now runs:

- source seed
- audit baseline
- projection rebuild all

Relevant target behavior was proven by dry-run output containing:

- Database\Seeders\CreateOnly\CreateTransactionWeekSeeder
- Database\Seeders\CreateOnly\CreateAuditBaselineSeeder
- php artisan projection:rebuild-indexes all

Raw seed-create-all-v3 remains source-only/debug style.

### SEED-PROJECTION-001 - Procurement projection drift resolved

Problem:

- supplier_invoices source rows existed.
- supplier_invoice_list_projection had only partial/manual rows.
- procurement/faktur UI read projection, not source table.

Manual proof showed projection rebuild fixed source-to-projection visibility.

Final create-all-v3 now rebuilds projections automatically.

### SEED-DATE-001 - CreateOnly active-month calendar helper

Added helper:

- database/seeders/CreateOnly/Support/CreateOnlySeedCalendar.php

Purpose:

- avoid hardcoded 2026-05 dates.
- use active current month dates.
- allow next-month due dates where appropriate.

Applied to:

- database/seeders/CreateOnly/CreateSupplierProcurementSeeder.php
- database/seeders/CreateOnly/CreateOperationalExpenseSeeder.php
- database/seeders/CreateOnly/CreatePayrollDisbursementSeeder.php
- database/seeders/CreateOnly/CreateEmployeeDebtSeeder.php
- database/seeders/CreateOnly/CreateEmployeeDebtPaymentSeeder.php
- database/seeders/CreateOnly/CreateEmployeeDebtAdjustmentSeeder.php

### SEED-DATE-002 - Non-transaction active-month sanity proof

Local sanity output after the date fixes:

supplier_invoices_june = 24
supplier_payments_june = 24
operational_expenses_june = 45
payroll_june = 6
employee_debts_june = 13
employee_debt_payments_june = 6
employee_debt_adjustments_june = 3
notes = 0

Meaning:

- non-transaction source seed is now aligned with June 2026.
- create transaction seed was still absent at that proof point.

### SEED-TXN-001 - Minimal weekly create transaction seed

Added file:

- database/seeders/CreateOnly/CreateTransactionWeekSeeder.php

Wired make target:

- seed-transaction-week

Wired into:

- seed-create-all-v3

Implementation decision:

- Create note seed must use App\Application\Note\UseCases\CreateTransactionWorkspaceHandler.
- Do not raw-insert notes/work_items/payments/projections.

Reason:

CreateTransactionWorkspaceHandler performs the real mutation path:

- idempotency replay/start/succeed
- transaction begin/commit/rollback
- note creation
- work item persistence
- inventory issue for store stock lines
- note total update
- inline payment recording
- payment component allocations
- audit log
- note history projection sync

### SEED-TXN-002 - Weekly transaction seed final local proof

Command:

php artisan migrate:fresh --seed
make create-all-v3

Then tinker count proof.

Final local output:

create-only transaction week notes: planned=6 created=6 replayed=0

Projection rebuild output:

Procurement projection: 24/24
Supplier projection: 78/78
Note projection: 6/6

Final counts:

notes = 6
work_items = 6
work_item_service_details = 6
work_item_store_stock_lines = 3
work_item_external_purchase_lines = 2
customer_payments = 5
payment_component_allocations = 9
inventory_stock_out_for_work_items = 3
note_history_projection = 6
transaction_note_totals.total_notes = 6
transaction_note_totals.total_rupiah = 1225000
external_package_note.customer_name = Seed Customer Mingguan 006
external_package_note.total_rupiah = 275000

Interpretation:

- weekly transaction seed is GREEN at minimal level.
- 6 notes were created through the real create transaction handler.
- note projection rebuild is GREEN.
- store stock inventory issue path is covered.
- external purchase path is covered.
- package auto split store-stock multi-product path is covered.
- external package path is covered.
- inline payment path is covered.
- 5 payments are expected because one note intentionally skips payment.

## Bugs Encountered And Resolved

### BUG-001 - Role constant mismatch

Failure:

Undefined constant App\Core\IdentityAccess\Role\Role::CASHIER

Cause:

Role class has Role::KASIR, not Role::CASHIER.

Fix:

Use Role::KASIR.

### BUG-002 - External package component amount zero

Failure:

Create transaction week seed failed: Amount komponen harus > 0.

Cause:

External package auto-split composer expects external_purchase_lines.0.total_rupiah for package mode.
Payload using qty/unit_cost without total_rupiah did not trigger package residual composition.

Fix:

External package payload must provide total_rupiah for package auto split external purchase.

Final proof after fix is GREEN.

## Current Progress Estimate

Existing CreateOnly seed stabilization:

90%+

Full serious create-all seed system:

55-60%

Reason full system is not higher:

- monthly normal 100-200 juta is not implemented.
- peak 500 juta/month is not implemented.
- stress 6-8 miliar/month is not implemented.
- refund scaffold is not implemented.
- report owner-readable wording is not patched.
- operational profit report sanity after transaction seed is not yet proven.
- full make verify after all seed changes is not yet proven.

## GAP

- No report sanity proof after weekly transaction seed.
- No PDF/Excel report proof after weekly transaction seed.
- No monthly normal profile.
- No peak profile.
- No stress profile.
- No refund scaffold.
- No full make verify proof after current seed changes.
- No handoff closure.

## DECISION

Do not scale dataset yet.

Before monthly/peak/stress, run operational profit sanity after CreateTransactionWeekSeeder is GREEN.

If profit is still negative or owner-confusing, analyze raw report inputs first.

Do not patch report labels before raw metric proof is clear.

## NEXT ACTIVE STEP

Run operational profit sanity query after weekly transaction seed.

Command:

php artisan tinker --execute="
\$from = '2026-06-01';
\$to = '2026-06-30';

dump([
    'customer_payments_sum' => DB::table('customer_payments')
        ->whereBetween('paid_at', [\$from.' 00:00:00', \$to.' 23:59:59'])
        ->sum('amount_rupiah'),

    'customer_refunds_sum' => DB::table('customer_refunds')
        ->whereBetween('refunded_at', [\$from.' 00:00:00', \$to.' 23:59:59'])
        ->sum('amount_rupiah'),

    'surplus_refund_paid_sum' => DB::table('note_revision_surplus_refund_payments')
        ->where('status', 'active')
        ->whereBetween('effective_date', [\$from, \$to])
        ->sum('amount_rupiah'),

    'external_purchase_lines_sum' => DB::table('work_item_external_purchase_lines')
        ->join('work_items', 'work_items.id', '=', 'work_item_external_purchase_lines.work_item_id')
        ->join('notes', 'notes.id', '=', 'work_items.note_id')
        ->whereBetween('notes.transaction_date', [\$from, \$to])
        ->sum('work_item_external_purchase_lines.line_total_rupiah'),

    'store_stock_cogs_stock_out_sum' => DB::table('inventory_movements')
        ->where('movement_type', 'stock_out')
        ->where('source_type', 'work_item_store_stock_line')
        ->whereBetween('tanggal_mutasi', [\$from, \$to])
        ->sum(DB::raw('ABS(total_cost_rupiah)')),

    'operational_expenses_sum' => DB::table('operational_expenses')
        ->whereBetween('expense_date', [\$from, \$to])
        ->sum('amount_rupiah'),

    'payroll_sum' => DB::table('payroll_disbursements')
        ->whereBetween('disbursement_date', [\$from.' 00:00:00', \$to.' 23:59:59'])
        ->sum('amount'),

    'employee_debt_cash_out_sum' => DB::table('employee_debts')
        ->whereBetween('created_at', [\$from.' 00:00:00', \$to.' 23:59:59'])
        ->sum('total_debt'),

    'notes_total_sum' => DB::table('notes')
        ->whereBetween('transaction_date', [\$from, \$to])
        ->sum('total_rupiah'),
]);
"

Expected rough shape:

- customer_payments_sum > 0
- notes_total_sum = 1225000
- external_purchase_lines_sum > 0
- store_stock_cogs_stock_out_sum > 0
- customer_refunds_sum likely 0
- operational/payroll/debt sums present

If laba is still negative:

- do not assume report bug.
- compare cash_in versus operational_expense/payroll/employee debt/product cost.
- likely weekly transaction seed is still too small versus monthly expense/payroll/debt source seed.
- choose whether to scale transaction seed or separate demo report profile.

## Opening Prompt For Next Session

Baca rules dulu sebelum jawab atau patch:

docs/04_lifecycle/handoff/README.md
docs/01_standards/0005_handoff_template.md
docs/01_standards/core/0010_scope_and_facts.md
docs/01_standards/core/0011_blueprint_first.md
docs/01_standards/core/0012_step_by_step_execution.md
docs/01_standards/core/0013_proof_and_progress.md
docs/01_standards/workflow/0020_response_structure.md
docs/01_standards/workflow/0021_active_step_policy.md
docs/01_standards/output/0033_terminal_command_delivery.md

Baca handoff aktif:

docs/04_lifecycle/handoff/0015_create_only_seed_system_stabilization_handoff.md

Cara kerja wajib:

- Local command output adalah source of truth tertinggi.
- Jangan mengarang file, status repo, hasil test, atau hasil command.
- Gunakan struktur FACT / GAP / DECISION / ACTIVE STEP / PROOF / NEXT.
- Blueprint-first sebelum implementasi.
- Satu response hanya satu active step.
- Jangan patch sebelum active scope jelas.
- Jangan bahas git kecuali diminta eksplisit.

Mulai dari NEXT ACTIVE STEP di handoff:
Run operational profit sanity query after weekly transaction seed.

