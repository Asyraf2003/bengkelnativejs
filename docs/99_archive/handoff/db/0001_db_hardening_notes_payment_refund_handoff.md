# Handoff 0001 - DB Hardening Notes, Payment, and Refund

Status: Active handoff  
Scope: DB hardening from `notes` timestamp focused verification into `customer_payments` / `customer_refunds` audit  
Owner: HyperPOS  

## 1. Final Goal

Selesaikan masalah DB hardening di `docs/03_blueprints/db/` satu per satu dengan urutan:

1. matrix,
2. source audit,
3. narrow patch blueprint,
4. RED or characterization proof,
5. minimal patch,
6. targeted proof,
7. focused proof,
8. docs alignment.

Workflow tetap mengikuti:

- `docs/03_blueprints/db/0001_temporal_audit_columns_blueprint.md`
- `docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md`
- `docs/03_blueprints/db/0003_db_hardening_workflow.md`
- `docs/03_blueprints/db/0004_db_audit_matrix.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`

## 2. Current Scope

Current completed group:

- `notes`

Current active next group:

- `customer_payments`
- `customer_refunds`
- related child/detail/linkage:
  - `customer_payment_cash_details`
  - `payment_allocations`
  - `payment_component_allocations`
  - `refund_component_allocations`
  - report/read paths using payment/refund dates

## 3. Completed Notes Slice

`notes` timestamp hardening reached Focused Verified.

Production files changed:

- `database/migrations/2026_05_15_000100_add_system_timestamps_to_notes_table.php`
- `app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php`

Test files changed:

- `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php`
- `tests/Feature/Note/NoteOperationalStatePersistenceFeatureTest.php`

Docs changed:

- `docs/03_blueprints/db/0004_db_audit_matrix.md`
- `docs/03_blueprints/db/0005_notes_timestamp_patch_blueprint.md`

Proof:

- RED targeted schema test failed with `Missing notes.created_at`: 1 failed, 2 passed, 20 assertions.
- GREEN targeted schema test passed: 3 tests, 21 assertions.
- GREEN writer persistence test passed: 2 tests, 16 assertions.
- Focused create flow test passed: 3 tests, 10 assertions.
- Focused blast-radius suite passed: 31 tests, 186 assertions.
- Final `git diff --check` was clean.
- Final anchors showed migration/writer/test timestamp content still present.

Notes timestamp decisions:

- `transaction_date` remains business/report date.
- `due_date` remains due date.
- `closed_at` / `reopened_at` remain lifecycle action timestamps.
- `created_at` means system row persistence time.
- `updated_at` means system row mutation time.
- Historical `created_at` for pre-patch rows must not be inferred from `transaction_date`.
- No timestamp index was added because no real timestamp read path was proven.
- No report semantics changed.
- Timestamps were not exposed to the `Note` domain object.

Remaining notes gaps:

- Full `make verify` was not proven in this slice.
- Browser/manual QA was not run.
- PostgreSQL runtime migration was not executed because PostgreSQL is not active.
- Timestamp read-path/index hardening is not approved.

## 4. Current Payment/Refund Audit Facts

Current migration facts:

`customer_payments`:

- Migration: `database/migrations/2026_03_14_000600_create_customer_payments_table.php`
- Columns:
  - `id`
  - `amount_rupiah`
  - `paid_at`
- Index:
  - `paid_at`

Payment method/cash detail alter:

- Migration: `database/migrations/2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php`
- Adds to `customer_payments`:
  - `payment_method`
  - index `payment_method`, `paid_at`
- Creates `customer_payment_cash_details`:
  - `customer_payment_id`
  - `amount_paid_rupiah`
  - `amount_received_rupiah`
  - `change_rupiah`
- FK:
  - `customer_payment_cash_details.customer_payment_id` references `customer_payments.id`
  - current migration uses `cascadeOnDelete`

`customer_refunds`:

- Migration: `database/migrations/2026_03_15_000100_create_customer_refunds_table.php`
- Columns:
  - `id`
  - `customer_payment_id`
  - `note_id`
  - `amount_rupiah`
  - `refunded_at`
  - `reason`
- Indexes:
  - `customer_payment_id`
  - `note_id`
  - `refunded_at`
  - `customer_payment_id`, `note_id`

Current timestamp gap:

- `customer_payments` has no proven `created_at` / `updated_at`.
- `customer_refunds` has no proven `created_at` / `updated_at`.
- `customer_payment_cash_details` has no proven `created_at` / `updated_at`.

## 5. Current Payment/Refund Writer Facts

Payment writer:

- `app/Adapters/Out/Payment/DatabaseCustomerPaymentWriterAdapter.php`

Current behavior:

- Inserts into `customer_payments`:
  - `id`
  - `amount_rupiah`
  - `payment_method`
  - `paid_at`
- Inserts into `customer_payment_cash_details` when cash detail exists:
  - `customer_payment_id`
  - `amount_paid_rupiah`
  - `amount_received_rupiah`
  - `change_rupiah`
- Does not write `created_at`.
- Does not write `updated_at`.

Refund writer:

- `app/Adapters/Out/Payment/DatabaseCustomerRefundWriterAdapter.php`

Current behavior:

- Inserts into `customer_refunds`:
  - `id`
  - `customer_payment_id`
  - `note_id`
  - `amount_rupiah`
  - `refunded_at`
  - `reason`
- Does not write `created_at`.
- Does not write `updated_at`.

Reader/domain facts:

- `CustomerPayment` domain object stores:
  - id
  - amount
  - paidAt
  - paymentMethod
- `CustomerRefund` domain object stores:
  - id
  - customerPaymentId
  - noteId
  - amount
  - refundedAt
  - reason
- No system timestamp is currently part of the payment/refund domain objects.
- This matches the prior `notes` approach where system timestamps remain DB/persistence concern unless a use case needs them.

## 6. Current Payment/Refund Use Case Facts

Payment write paths:

- `app/Application/Payment/UseCases/RecordCustomerPaymentHandler.php`
- `app/Application/Payment/UseCases/RecordAndAllocateNotePaymentHandler.php`
- `app/Application/Payment/Services/RecordAndAllocateNotePaymentOperation.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`

Refund write paths:

- `app/Application/Payment/UseCases/RecordCustomerRefundHandler.php`
- `app/Application/Payment/Services/RecordCustomerRefundOperation.php`
- `app/Application/Payment/Services/RecordCustomerRefundTransaction.php`

Important behavior:

- Payment creation uses `paid_at` as business/payment date.
- Refund creation uses `refunded_at` as refund date.
- Payment/refund handlers also sync projection/audit/lifecycle behavior.
- Refund path can trigger inventory reversal and note refund lifecycle logic.
- Patch must not change payment/refund business date semantics.

## 7. Baseline Payment/Refund Proof

Focused baseline before any payment/refund patch:

Command group covered:

- `tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php`
- `tests/Feature/Payment/RecordAndAllocateNotePaymentFeatureTest.php`
- `tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
- `tests/Feature/Payment/RecordSelectedRowsCustomerRefundFeatureTest.php`
- `tests/Feature/Payment/DatabasePaymentAllocationReaderAdapterFeatureTest.php`

Proof:

- PASS: 13 tests, 45 assertions.

Meaning:

- Current payment/refund behavior is green before timestamp patch.
- Any future RED must be a narrow characterization of missing timestamp columns or writer timestamp behavior, not a broad behavior regression.
- If future patch breaks these tests, inspect the timestamp compatibility and direct insert paths first.

## 8. Current Risks

### 8.1 Timestamp ambiguity

Risk:

- `paid_at` and `refunded_at` are business/domain dates.
- There is no row persistence timestamp for customer payment/refund records.
- This creates audit ambiguity similar to the original `notes` issue.

Initial likely recommendation:

- Add DB-level `created_at` for `customer_payments` and `customer_refunds`.
- Add `updated_at` only if mutation paths exist or future updates are expected.
- Keep timestamps nullable or safely backfilled to preserve direct insert compatibility.
- Do not infer historical `created_at` from `paid_at` or `refunded_at`.

### 8.2 Direct insert blast radius

Risk:

- Many tests, seeders, reporting fixtures, dashboard fixtures, note tests, payment tests, refund tests, and export tests insert `customer_payments` and `customer_refunds` directly.
- A non-null timestamp patch without safe default/backfill can break a large part of the suite.

Initial likely policy:

- Follow `notes` precedent:
  - characterization RED first,
  - nullable-safe/backfilled migration,
  - writer timestamp proof,
  - focused blast-radius proof.

### 8.3 Cash detail cascade delete

Risk:

- `customer_payment_cash_details` currently uses `cascadeOnDelete` to `customer_payments`.
- DB Blueprint 0002 warns against cascade delete financial history.
- This may be acceptable only if:
  - `customer_payments` is never deleted, and
  - cash detail is considered dependent detail, and
  - delete behavior is protected elsewhere.
- This is not yet proven.

Required audit:

- Search for delete paths on `customer_payments`.
- Verify whether payment rows are immutable and never hard-deleted.
- Decide whether `cascadeOnDelete` is safe, deferred legacy risk, or must be changed in a later migration.

## 9. Locked Decisions

- Do not reopen `notes` unless new regression proof appears.
- Do not start payment/refund schema patch before matrix row is updated.
- Do not rename locked date terms:
  - `paid_at`
  - `refunded_at`
- Do not use `created_at` as report period.
- Do not use `created_at` as payment date.
- Do not use `created_at` as refund date.
- Do not expose DB system timestamps into domain objects unless a use case requires them.
- Do not add timestamp indexes without read path proof.
- Do not change payment/refund reporting semantics.
- Do not touch supplier, inventory, UI, mobile/API, or PostgreSQL migration in the next payment/refund timestamp slice.

## 10. Safest Next Step

Update `docs/03_blueprints/db/0004_db_audit_matrix.md` rows for:

- `customer_payments`
- `customer_refunds`

Move both from `Reported` to `Audited`.

Required matrix content:

- migration facts,
- current writer facts,
- direct insert blast-radius risk,
- baseline proof,
- cascade delete risk for cash details,
- patch allowed now: No,
- required proof before patch.

Then create a narrow patch blueprint only if the matrix row is accepted.

## 11. Recommended Next Commands

Inspect delete paths before any patch:

~~~bash
rg -n "customer_payments.*delete|delete\\(\\).*customer_payments|DB::table\\('customer_payments'\\)->delete|from\\('customer_payments'\\).*delete|cascadeOnDelete" app tests database --glob '*.php'
~~~

Inspect direct insert blast radius narrowly:

~~~bash
rg -n "DB::table\\('customer_payments'\\)->(insert|updateOrInsert)|DB::table\\('customer_refunds'\\)->(insert|updateOrInsert)" tests database app --glob '*.php'
~~~

Create RED only after matrix audit is updated:

~~~bash
php artisan test tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php tests/Feature/Payment/RecordCustomerRefundFeatureTest.php
~~~

## 12. Opening Prompt For Next Session

Lanjut HyperPOS DB hardening dari docs/03_blueprints/db/. notes timestamp slice sudah Focused Verified dengan RED missing notes.created_at, targeted GREEN, focused 31/186, docs aligned, diff check clean. Jangan reopen notes kecuali ada regression proof. Current active group: customer_payments + customer_refunds. Payment/refund audit already collected: migrations show paid_at/refunded_at but no system timestamps, writers do not write timestamps, domain objects do not expose timestamps, baseline payment/refund tests pass 13/45, and customer_payment_cash_details has cascadeOnDelete risk that must be audited. Next safest step: update 0004_db_audit_matrix.md rows for customer_payments and customer_refunds to Audited, no schema patch yet.
