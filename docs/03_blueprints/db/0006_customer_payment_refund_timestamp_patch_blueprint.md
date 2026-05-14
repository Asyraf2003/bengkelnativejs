# DB Blueprint 0006 - Customer Payment Refund Timestamp Patch Blueprint

Status: Patch Blueprinted
Scope: `customer_payments`, `customer_refunds`, and `customer_payment_cash_details` system row timestamp hardening
Owner: HyperPOS

## 1. Active Table Group

Table groups:

- `customer_payments`
- `customer_refunds`

Related table:

- `customer_payment_cash_details`

Category:

- Payment/source financial table
- Refund/source financial table
- Payment cash detail table

Source-of-truth status:

- P0 finance-sensitive payment/refund source group

## 2. Exact Problem

`customer_payments`, `customer_refunds`, and `customer_payment_cash_details` currently store business/payment/refund values but do not have proven system row timestamps.

Current proven dates:

- `customer_payments.paid_at` is the payment/report date.
- `customer_refunds.refunded_at` is the refund/report date.

Current gap:

- `customer_payments.created_at` is missing.
- `customer_payments.updated_at` is missing.
- `customer_refunds.created_at` is missing.
- `customer_refunds.updated_at` is missing.
- `customer_payment_cash_details.created_at` is missing.
- `customer_payment_cash_details.updated_at` is missing.
- `DatabaseCustomerPaymentWriterAdapter` inserts payment and cash detail rows without system timestamps.
- `DatabaseCustomerRefundWriterAdapter` inserts refund rows without system timestamps.
- Many tests insert payment/refund rows directly, so a naive non-null timestamp patch can break fixtures.

## 3. Current Proven Schema

Payment base migration:

- `database/migrations/2026_03_14_000600_create_customer_payments_table.php`

Proven `customer_payments` columns:

- `id`
- `amount_rupiah`
- `paid_at`

Proven indexes:

- `paid_at`

Payment method / cash detail migration:

- `database/migrations/2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php`

Proven added `customer_payments` column:

- `payment_method`

Proven added index:

- `payment_method`, `paid_at`

Proven `customer_payment_cash_details` columns:

- `customer_payment_id`
- `amount_paid_rupiah`
- `amount_received_rupiah`
- `change_rupiah`

Proven `customer_payment_cash_details` constraint:

- `customer_payment_id` references `customer_payments.id`
- current migration uses `cascadeOnDelete`

Proven `customer_payment_cash_details` index:

- `change_rupiah`

Refund migration:

- `database/migrations/2026_03_15_000100_create_customer_refunds_table.php`

Proven `customer_refunds` columns:

- `id`
- `customer_payment_id`
- `note_id`
- `amount_rupiah`
- `refunded_at`
- `reason`

Proven indexes:

- `customer_payment_id`
- `note_id`
- `refunded_at`
- `customer_payment_id`, `note_id`

## 4. Current Proven Writer Behavior

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
  - cash received/paid/change fields
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

## 5. Current Proven Fixture/Test Risk

Direct inserts into payment/refund tables exist across payment, reporting, exports, note, database FK/index, dashboard, and operational finance tests.

Known direct insert targets include:

- `customer_payments`
- `customer_refunds`
- `customer_payment_cash_details`
- `payment_allocations`
- `payment_component_allocations`
- `refund_component_allocations`

Risk:

- A `NOT NULL` timestamp migration without nullable compatibility or explicit fixture migration can break many tests.
- A writer-only patch will not cover direct test fixtures.
- A report query must not silently switch from `paid_at` or `refunded_at` to `created_at`.

## 6. Recommended Schema Change

Create a new migration. Do not edit old migrations.

Recommended first patch:

- Add nullable-safe/backfilled `created_at` to `customer_payments`.
- Add nullable-safe/backfilled `updated_at` to `customer_payments`.
- Add nullable-safe/backfilled `created_at` to `customer_refunds`.
- Add nullable-safe/backfilled `updated_at` to `customer_refunds`.
- Add nullable-safe/backfilled `created_at` to `customer_payment_cash_details`.
- Add nullable-safe/backfilled `updated_at` to `customer_payment_cash_details`.
- Keep `paid_at` as payment/report date.
- Keep `refunded_at` as refund/report date.
- Do not expose timestamps to payment/refund domain objects unless a use case needs them.
- Do not add timestamp indexes in this slice.

Preferred column semantics:

- `created_at`: system row creation/persistence timestamp.
- `updated_at`: system row mutation timestamp.

## 7. Backfill Policy

Do not copy `paid_at` into `created_at`.

Reason:

- `paid_at` is payment/report date.
- `created_at` is system persistence time.

Do not copy `refunded_at` into `created_at`.

Reason:

- `refunded_at` is refund/report date.
- `created_at` is system persistence time.

Safe policy:

- Keep timestamp columns nullable to preserve direct insert compatibility.
- Backfill existing rows with migration execution time only if the migration explicitly updates existing rows.
- Record that historical creation time for pre-patch rows remains approximate/unknown.
- Writer must set `created_at` and `updated_at` for new payment/refund rows going forward.
- For immutable insert-only rows, initial `updated_at` should equal `created_at`.

## 8. Domain Impact

Expected domain impact:

- No change to `CustomerPayment`.
- No change to `CustomerPaymentCashDetail`.
- No change to `CustomerRefund`.
- No change to payment/refund business dates.
- No change to allocation semantics.
- No change to report period semantics.

Forbidden impact:

- Do not use `created_at` for payment report period.
- Do not use `created_at` for refund report period.
- Do not rename locked domain terms.
- Do not infer historical payment/refund timing from system timestamps.

## 9. Report Impact

Expected report impact:

- None, if reports continue using `paid_at` and `refunded_at`.

Required proof:

- Focused reporting tests must still pass if touched indirectly.
- No report query should silently switch to `created_at`.
- Transaction/cash ledger tests with direct inserts must remain compatible.

## 10. PostgreSQL Readiness Impact

Patch must avoid:

- MySQL-only timestamp semantics as business rule.
- relying on implicit timestamp defaults as domain truth.
- relying on unsigned-only behavior.
- JSON as timestamp truth.

Preferred:

- Explicit application timestamp writes in payment/refund writers.
- Laravel schema/DB operations that remain portable enough for future PostgreSQL migration planning.
- Nullable direct-insert compatibility until fixture migration is intentionally handled.

## 11. CRUD And Read Path Impact

No new timestamp index is approved in this slice.

Reason:

- No proven read path currently needs sorting/filtering by `created_at` or `updated_at`.
- Index hardening must follow real read path proof, not vibes with a clipboard.

## 12. Cascade Delete Risk

Current proven risk:

- `customer_payment_cash_details.customer_payment_id` currently uses `cascadeOnDelete` to `customer_payments`.

Current local delete audit:

- No proven application/test hard-delete path for `customer_payments` was found by the focused grep.
- The only proven customer payment delete/cascade risk in this slice is the FK cascade definition itself.

Decision:

- Do not change cascade behavior in the timestamp patch.
- Keep cascade as residual risk.
- Revisit only after hard-delete behavior is proven, owner accepts a separate risk decision, or a separate FK hardening slice is opened.

## 13. Files To Touch In Patch Slice

Expected files:

- new migration under `database/migrations/`
- `app/Adapters/Out/Payment/DatabaseCustomerPaymentWriterAdapter.php`
- `app/Adapters/Out/Payment/DatabaseCustomerRefundWriterAdapter.php`
- focused migration/database test
- focused payment writer feature test
- focused refund writer feature test
- `docs/03_blueprints/db/0004_db_audit_matrix.md` after proof

Likely test files:

- new or existing database schema test for payment/refund timestamp columns
- `tests/Feature/Payment/RecordCustomerPaymentFeatureTest.php`
- `tests/Feature/Payment/RecordCustomerRefundFeatureTest.php`
- focused refund selected-row test if writer path is shared
- focused reporting/cash ledger tests only as blast-radius proof, not as primary patch location

## 14. Files Not To Touch

Do not touch in this slice:

- payment allocation logic
- refund allocation algorithm
- note revision finance behavior
- inventory movement logic
- supplier invoice/payment/receipt logic
- UI
- API/mobile
- PostgreSQL runtime implementation
- Go API
- report date semantics
- `customer_payment_cash_details` cascade behavior

## 15. Characterization Proof Plan

Minimum RED proof should prove the current schema gap before implementation:

- `customer_payments` missing `created_at`.
- `customer_payments` missing `updated_at`.
- `customer_refunds` missing `created_at`.
- `customer_refunds` missing `updated_at`.
- `customer_payment_cash_details` missing `created_at`.
- `customer_payment_cash_details` missing `updated_at`.

Expected RED shape:

- Focused database schema test fails on the first missing timestamp column.
- Do not proceed to writer/schema patch until RED output is captured.

## 16. GREEN Proof Plan

Minimum proof after patch:

- `php -l` for changed PHP files.
- Targeted migration/database test for payment/refund/cash detail timestamp columns.
- Targeted payment writer test proving new payment rows receive `created_at` and `updated_at`.
- Targeted payment cash detail writer test proving cash detail rows receive `created_at` and `updated_at`.
- Targeted refund writer test proving new refund rows receive `created_at` and `updated_at`.
- Focused payment/refund baseline tests previously used for this slice.
- Focused direct-insert/reporting compatibility tests most likely to break.
- `git diff --check`.

## 17. Rollback Or Defer Criteria

Stop or defer if:

- patch requires changing many unrelated fixtures manually;
- report semantics would change;
- historical `created_at` would be falsely inferred from `paid_at` or `refunded_at`;
- direct insert compatibility cannot be preserved cleanly;
- writer timestamp behavior cannot be tested narrowly;
- cascade delete behavior becomes part of the change.

## 18. Current Decision

Patch is not yet implemented.

Next safe step:

- Add RED characterization test for missing payment/refund/cash detail timestamp columns.
- Keep schema and writer patch blocked until RED proof exists.

## 19. Handoff Archive

DB hardening handoff archive:

- [DB hardening handoff folder](../../99_archive/handoff/db/)
- [Current DB hardening handoff](../../99_archive/handoff/db/0001_db_hardening_notes_payment_refund_handoff.md)
