# DB Blueprint 0007 - Allocation Tables Timestamp Immutability Patch Blueprint

Status: Focused Verified
Scope: `payment_allocations`, `payment_component_allocations`, and `refund_component_allocations` row timestamp and immutability hardening
Owner: HyperPOS

## 1. Active Table Group

Table groups:

- `payment_allocations`
- `payment_component_allocations`
- `refund_component_allocations`

Category:

- Payment allocation table
- Payment component allocation table
- Refund component allocation table

Source-of-truth status:

- P0 allocation truth group
- Finance-sensitive settlement/read-model bridge

## 2. Exact Problem

Allocation rows are finance-sensitive rows that connect payments/refunds to notes, work items, and components.

Current proven gap:

- `payment_allocations.created_at` is not proven.
- `payment_allocations.updated_at` is not proven.
- `payment_component_allocations.created_at` is not proven.
- `payment_component_allocations.updated_at` is not proven.
- `refund_component_allocations.created_at` is not proven.
- `refund_component_allocations.updated_at` is not proven.
- Allocation writer adapters are not yet proven to write system row timestamps.
- Direct inserts exist across tests, seeders, reporting, dashboard, note lifecycle, refund lifecycle, revision, and finance regression fixtures.

Risk:

- Adding non-null timestamps without nullable compatibility can break many fixtures.
- Adding timestamp indexes without read-path proof can create unnecessary index bloat.
- Changing allocation semantics can break settlement, refund, note history, cash ledger, transaction summary, and UI-derived views.
- Treating allocation timestamps as business/payment/refund dates would be incorrect.

## 3. Current Proven Schema

Legacy note-level allocation migration:

- `database/migrations/2026_03_14_000700_create_payment_allocations_table.php`

Proven `payment_allocations` columns:

- `id`
- `customer_payment_id`
- `note_id`
- `amount_rupiah`

Proven indexes:

- `customer_payment_id`
- `note_id`
- `customer_payment_id`, `note_id` via hot-path index migration

Component payment allocation migration:

- `database/migrations/2026_04_02_000800_create_payment_component_allocations_table.php`

Proven `payment_component_allocations` columns:

- `id`
- `customer_payment_id`
- `note_id`
- `work_item_id`
- `component_type`
- `component_ref_id`
- `component_amount_rupiah_snapshot`
- `allocated_amount_rupiah`
- `allocation_priority`

Proven indexes/constraints:

- `note_id`, `work_item_id`
- `note_id`, `component_type`, `component_ref_id`
- unique `customer_payment_id`, `component_type`, `component_ref_id`
- `customer_payment_id`, `note_id` via hot-path index migration
- `work_item_id` via hot-path index migration

Component refund allocation migration:

- `database/migrations/2026_04_02_000900_create_refund_component_allocations_table.php`

Proven `refund_component_allocations` columns:

- `id`
- `customer_refund_id`
- `customer_payment_id`
- `note_id`
- `work_item_id`
- `component_type`
- `component_ref_id`
- `refunded_amount_rupiah`
- `refund_priority`

Proven indexes/constraints:

- `note_id`, `work_item_id`
- `customer_payment_id`, `note_id`
- `note_id`, `component_type`, `component_ref_id`
- unique `customer_refund_id`, `component_type`, `component_ref_id`
- `work_item_id` via hot-path index migration

## 4. Current Proven Foreign Key Behavior

FK migration:

- `database/migrations/2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php`

Proven FK behavior:

- `payment_allocations.customer_payment_id` references `customer_payments.id` with `restrictOnDelete`.
- `payment_allocations.note_id` references `notes.id` with `restrictOnDelete`.
- `payment_component_allocations.customer_payment_id` references `customer_payments.id` with `restrictOnDelete`.
- `payment_component_allocations.note_id` references `notes.id` with `restrictOnDelete`.
- `payment_component_allocations.work_item_id` references `work_items.id` with `restrictOnDelete`.
- `refund_component_allocations.customer_refund_id` references `customer_refunds.id` with `restrictOnDelete`.
- `refund_component_allocations.customer_payment_id` references `customer_payments.id` with `restrictOnDelete`.
- `refund_component_allocations.note_id` references `notes.id` with `restrictOnDelete`.
- `refund_component_allocations.work_item_id` references `work_items.id` with `restrictOnDelete`.

Decision:

- Do not change FK/delete semantics in this timestamp patch.
- Current restrict-on-delete behavior is preserved.

## 5. Current Proven Usage

Allocation tables are used by:

- payment allocation writers/readers
- component payment allocation writers/readers
- component refund allocation writers/readers
- customer payment/refund readers
- transaction summary reporting
- cash ledger reporting
- note history aggregation
- operational profit metrics
- work item delete protection
- note revision/replacement flows
- refund lifecycle flows
- seeders and load seeders
- direct-insert tests across reporting, note, payment, refund, dashboard, and lifecycle coverage

Known high-risk semantics:

- `payment_allocations` is still a legacy fallback path.
- `payment_component_allocations` is the component-backed allocation path.
- `refund_component_allocations` is refund ledger/audit input and also protects historical work items.
- Report and UI-adjacent outputs can depend on allocation readers.

## 6. Recommended Schema Change

Create a new migration. Do not edit old migrations.

Recommended first patch:

- Add nullable-safe/backfilled `created_at` to `payment_allocations`.
- Add nullable-safe/backfilled `updated_at` to `payment_allocations`.
- Add nullable-safe/backfilled `created_at` to `payment_component_allocations`.
- Add nullable-safe/backfilled `updated_at` to `payment_component_allocations`.
- Add nullable-safe/backfilled `created_at` to `refund_component_allocations`.
- Add nullable-safe/backfilled `updated_at` to `refund_component_allocations`.

Preferred semantics:

- `created_at`: system row creation/persistence timestamp.
- `updated_at`: system row mutation timestamp.
- For immutable insert-only allocation rows, initial `updated_at` equals `created_at`.

Do not add timestamp indexes in this slice.

Reason:

- No proven read path currently filters/sorts allocation rows by `created_at` or `updated_at`.
- Existing hot-path indexes already target payment/note/work item/component lookup paths.
- Index hardening must follow real read-path proof.

## 7. Backfill Policy

Do not infer allocation `created_at` from payment business date, refund business date, note transaction date, or component order.

Safe policy:

- Keep timestamp columns nullable to preserve direct insert compatibility.
- Backfill existing rows with migration execution time only if the migration explicitly updates existing rows.
- Record that historical creation time for pre-patch rows remains approximate/unknown.
- Writers must set `created_at` and `updated_at` for new allocation rows going forward.

## 8. Domain And Report Impact

Expected domain impact:

- No change to payment/refund allocation domain semantics.
- No change to outstanding/billing/settlement math.
- No change to refund math.
- No change to report period semantics.
- No change to current-vs-historical projection semantics.
- No change to legacy `payment_allocations` fallback behavior.

Forbidden impact:

- Do not use allocation `created_at` as payment/report/refund date.
- Do not replace `paid_at` or `refunded_at`.
- Do not change allocation priority semantics.
- Do not change component snapshot semantics.
- Do not change note revision reconciliation semantics.
- Do not remove legacy allocation fallback.
- Do not make allocation rows mutable unless a separate use case proves it.

## 9. PostgreSQL Readiness Impact

Patch must avoid:

- relying on implicit MySQL timestamp defaults as domain truth;
- unsigned-only assumptions;
- JSON timestamp truth;
- DB engine-specific timestamp side effects;
- non-portable index claims.

Preferred:

- explicit application timestamp writes in allocation writers;
- nullable direct-insert compatibility until fixture migration is intentionally handled;
- no timestamp indexes without proven read-path demand.

## 10. Files To Touch In Patch Slice

Expected production files:

- new migration under `database/migrations/`
- `app/Adapters/Out/Payment/DatabasePaymentAllocationWriterAdapter.php`
- `app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php`
- `app/Adapters/Out/Payment/DatabaseRefundComponentAllocationWriterAdapter.php`

Expected test files:

- focused database schema test for allocation timestamp columns
- focused payment allocation writer test
- focused payment component allocation writer test
- focused refund component allocation writer test
- focused reporting/cash ledger/non-regression tests most likely to break

Docs:

- `docs/03_blueprints/db/0004_db_audit_matrix.md`
- this blueprint after proof

## 11. Files Not To Touch

Do not touch in this slice:

- payment allocation math
- refund allocation math
- note revision replacement logic
- current/historical projection semantics
- inventory movement logic
- supplier invoice/payment/receipt logic
- UI
- API/mobile
- Go API
- PostgreSQL runtime implementation
- existing hot-path indexes unless a real read-path proof requires it
- FK/delete semantics

## 12. Characterization Proof Plan

Minimum RED proof should prove the current schema gap before implementation:

- `payment_allocations` missing `created_at`.
- `payment_allocations` missing `updated_at`.
- `payment_component_allocations` missing `created_at`.
- `payment_component_allocations` missing `updated_at`.
- `refund_component_allocations` missing `created_at`.
- `refund_component_allocations` missing `updated_at`.

Expected RED shape:

- Focused database schema test fails on missing allocation timestamp columns.
- Do not proceed to writer/schema patch until RED output is captured.

## 13. GREEN Proof Plan

Minimum proof after patch:

- `php -l` for changed PHP files.
- Targeted migration/database test for allocation timestamp columns.
- Targeted legacy payment allocation writer test proving new rows receive `created_at` and `updated_at`.
- Targeted payment component allocation writer test proving new rows receive `created_at` and `updated_at`.
- Targeted refund component allocation writer test proving new rows receive `created_at` and `updated_at`.
- Focused allocation reader/writer tests.
- Focused reporting/cash ledger tests because allocation tables feed report outputs.
- Focused note history or note lifecycle tests if touched indirectly.
- `git diff --check`.

## 14. Rollback Or Defer Criteria

Stop or defer if:

- patch requires changing many unrelated fixtures manually;
- settlement math would change;
- report semantics would change;
- current/historical projection semantics would change;
- legacy allocation fallback would be removed or weakened;
- historical `created_at` would be falsely inferred from payment/refund/note business dates;
- direct insert compatibility cannot be preserved cleanly;
- writer timestamp behavior cannot be tested narrowly;
- FK/delete behavior becomes part of the change.

## 15. Current Decision

Patch is implemented and focused verified.

Implemented behavior:

- Added nullable `created_at` and `updated_at` columns to `payment_allocations`.
- Added nullable `created_at` and `updated_at` columns to `payment_component_allocations`.
- Added nullable `created_at` and `updated_at` columns to `refund_component_allocations`.
- Historical/pre-patch rows are backfilled with migration execution time as approximate system timestamp.
- New legacy payment allocation rows write `created_at` and `updated_at`.
- New payment component allocation rows write `created_at` and `updated_at`.
- New refund component allocation rows write `created_at` and `updated_at`.
- Initial `updated_at` equals `created_at` for insert-only rows.

Preserved decisions:

- Allocation timestamps are not business/report dates.
- No timestamp index was added.
- FK/delete semantics were not changed.
- Legacy `payment_allocations` fallback was preserved.
- Allocation math, refund math, note revision replacement, current/historical projection semantics, UI, API/mobile, supplier, inventory, Go API, and PostgreSQL runtime implementation were not touched.

Focused proof captured:

- RED schema proof: missing timestamp columns on `payment_allocations`, `payment_component_allocations`, and `refund_component_allocations`.
- GREEN schema proof: `AllocationTimestampSchemaTest` passed.
- RED writer proof: legacy payment allocation, payment component allocation, and refund component allocation writer rows had null timestamps before writer patch.
- GREEN writer proof: targeted operational timestamp tests passed, 3 tests / 21 assertions.
- Focused baseline/blast-radius proof: allocation schema, allocation writer/reader, reporting, cash ledger, transaction summary, transaction report dataset, and operational profit tests passed, 26 tests / 141 assertions.
- Syntax proof passed for changed migration and writer adapters.

Remaining gaps:

- Full `make verify` was not rerun after this allocation slice unless owner runs it separately.
- Browser/manual QA was not run.
- PostgreSQL runtime migration was not run.
- Wider full-suite regression proof remains deferred unless separately requested.

## 16. Focused Verification Result

Status after implementation:

- Focused Verified.

Production files changed:

- `database/migrations/2026_05_15_000002_add_operational_timestamps_to_allocation_tables.php`
- `app/Adapters/Out/Payment/DatabasePaymentAllocationWriterAdapter.php`
- `app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php`
- `app/Adapters/Out/Payment/DatabaseRefundComponentAllocationWriterAdapter.php`

Test files changed:

- `tests/Feature/Database/AllocationTimestampSchemaTest.php`
- `tests/Feature/Payment/AllocateCustomerPaymentFeatureTest.php`
- `tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`

Verification proof:

- `php -l database/migrations/2026_05_15_000002_add_operational_timestamps_to_allocation_tables.php`
- `php -l app/Adapters/Out/Payment/DatabasePaymentAllocationWriterAdapter.php`
- `php -l app/Adapters/Out/Payment/DatabasePaymentComponentAllocationWriterAdapter.php`
- `php -l app/Adapters/Out/Payment/DatabaseRefundComponentAllocationWriterAdapter.php`
- `php -l tests/Feature/Payment/AllocateCustomerPaymentFeatureTest.php`
- `php -l tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php`
- `php -l tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- `php artisan test tests/Feature/Database/AllocationTimestampSchemaTest.php`
- `php artisan test tests/Feature/Payment/AllocateCustomerPaymentFeatureTest.php tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php --filter=operational_timestamps`
- `php artisan test tests/Feature/Database/AllocationTimestampSchemaTest.php tests/Feature/Payment/AllocateCustomerPaymentFeatureTest.php tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php tests/Feature/Reporting/TransactionCashLedgerReportingQueryFeatureTest.php tests/Feature/Reporting/TransactionSummaryReportingQueryFeatureTest.php tests/Feature/Reporting/GetTransactionReportDatasetFeatureTest.php tests/Feature/Reporting/GetOperationalProfitSummaryFeatureTest.php`
- Result: 26 tests passed, 141 assertions.

Compatibility notes:

- Timestamp columns remain nullable for direct insert compatibility.
- Writer paths now set timestamps explicitly for new allocation rows.
- Existing allocation/report/refund/settlement semantics remain unchanged.
- Existing FK/delete semantics remain unchanged.
- No new timestamp index exists in this slice.
