# DB Blueprint 0001 - Temporal Audit Columns

Status: Draft  
Scope: database/migrations temporal column audit  
Date: 2026-05-14  
Owner: HyperPOS  

## 1. Purpose

Blueprint ini menetapkan standar kolom tanggal/waktu di database HyperPOS agar sistem bisa membedakan:

1. tanggal bisnis / tanggal efektif,
2. tanggal aksi domain terjadi,
3. tanggal row dicatat atau diubah oleh sistem.

Tujuan utamanya adalah mencegah ambiguitas audit pada transaksi, stok, pembayaran, refund, supplier flow, revisi nota, dan laporan finansial.

## 2. Problem

Sebagian migration sudah menyimpan dua jenis tanggal, tetapi sebagian lain hanya menyimpan tanggal bisnis, hanya timestamp sistem, atau tidak menyimpan tanggal sama sekali.

Masalah utama:

- pembayaran bisa punya `paid_at`, tetapi tidak selalu punya `created_at`;
- refund bisa punya `refunded_at`, tetapi tidak selalu punya `created_at`;
- nota bisa punya `transaction_date`, tetapi tidak selalu punya `created_at`;
- inventory movement bisa punya `tanggal_mutasi`, tetapi tidak selalu punya `created_at`;
- edit/version table punya `changed_at`, tetapi tidak selalu punya row-created timestamp;
- beberapa table line/allocation tidak punya tanggal sama sekali dan hanya bergantung pada parent table.

Untuk domain finance, satu tanggal tidak cukup. Sistem perlu tahu apakah tanggal itu adalah tanggal kejadian bisnis atau tanggal pencatatan sistem. Karena rupanya uang tidak mau tunduk pada optimisme manusia.

## 3. Evidence

Local command evidence:

- `rg` terhadap `database/migrations` menemukan migration yang memiliki kolom date/timestamp seperti `created_at`, `updated_at`, `transaction_date`, `paid_at`, `refunded_at`, `occurred_at`, `changed_at`, `effective_date`, `tanggal_*`, dan sejenisnya.
- `comm -23` terhadap seluruh migration menemukan migration yang tidak memiliki match date/timestamp berdasarkan pattern audit.

Daftar migration tanpa date/timestamp berdasarkan local proof:

- `database/migrations/0001_01_01_000001_create_cache_table.php`
- `database/migrations/2026_03_10_000100_create_actor_accesses_table.php`
- `database/migrations/2026_03_10_000200_create_admin_transaction_capability_states_table.php`
- `database/migrations/2026_03_11_000100_create_products_table.php`
- `database/migrations/2026_03_12_000100_create_suppliers_table.php`
- `database/migrations/2026_03_12_000300_create_supplier_invoice_lines_table.php`
- `database/migrations/2026_03_12_000500_create_supplier_receipt_lines_table.php`
- `database/migrations/2026_03_12_000700_create_product_inventory_table.php`
- `database/migrations/2026_03_13_000100_create_product_inventory_costing_table.php`
- `database/migrations/2026_03_14_000200_create_work_items_table.php`
- `database/migrations/2026_03_14_000300_create_work_item_service_details_table.php`
- `database/migrations/2026_03_14_000400_create_work_item_external_purchase_lines_table.php`
- `database/migrations/2026_03_14_000500_create_work_item_store_stock_lines_table.php`
- `database/migrations/2026_03_14_000700_create_payment_allocations_table.php`
- `database/migrations/2026_03_16_000100_create_admin_cashier_area_access_states_table.php`
- `database/migrations/2026_04_02_000800_create_payment_component_allocations_table.php`
- `database/migrations/2026_04_02_000900_create_refund_component_allocations_table.php`
- `database/migrations/2026_04_06_210000_add_v2_hot_path_indexes_for_existing_tables.php`
- `database/migrations/2026_04_06_220100_add_v2_procurement_inventory_foreign_keys.php`
- `database/migrations/2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php`
- `database/migrations/2026_04_06_220300_add_v2_note_mutation_workspace_foreign_keys.php`
- `database/migrations/2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php`
- `database/migrations/2026_04_17_013500_add_stock_threshold_columns_to_products_table.php`
- `database/migrations/2026_04_18_000200_alter_supplier_receipt_lines_add_snapshots.php`
- `database/migrations/2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php`
- `database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php`

## 4. Temporal Column Definitions

### 4.1 Business Date / Effective Date

Business date adalah tanggal yang dipakai untuk laporan, periode transaksi, perhitungan stok, pembayaran, hutang, refund, atau due date.

Contoh:

- `transaction_date`
- `due_date`
- `tanggal_pengiriman`
- `tanggal_terima`
- `tanggal_mutasi`
- `paid_at`
- `refunded_at`
- `payment_date`
- `disbursement_date`
- `expense_date`
- `effective_date`

Rule:

- Boleh berasal dari input user jika domain mendukung backdate.
- Harus valid untuk report.
- Tidak boleh dipakai sebagai satu-satunya bukti kapan row dibuat sistem.

### 4.2 Occurred At / Action Date

`occurred_at` adalah waktu aksi domain benar-benar terjadi menurut sistem.

Contoh aksi:

- payment recorded,
- refund recorded,
- note revision created,
- surplus disposition created,
- audit event emitted,
- mutation event emitted.

Rule:

- Default harus server time.
- Tidak boleh bebas diedit dari UI kecuali ada use case koreksi yang eksplisit.
- Untuk audit event, `occurred_at` adalah event time utama.

### 4.3 Created At / Updated At

`created_at` adalah waktu row disimpan pertama kali.  
`updated_at` adalah waktu row terakhir diubah.

Rule:

- `created_at` wajib untuk table mutable/source-of-truth finance baru.
- `updated_at` wajib hanya untuk table yang memang bisa berubah.
- Immutable ledger/event/allocation table boleh hanya punya `created_at`.
- Projection/read model boleh memakai `projected_at`, bukan wajib `updated_at`.

### 4.4 Changed At

`changed_at` dipakai oleh version/history table untuk mencatat waktu perubahan master data atau invoice revision.

Rule:

- Untuk version table, `changed_at` adalah event/action time.
- Jika `changed_at` selalu server-generated, `created_at` tambahan tidak wajib.
- Jika `changed_at` bisa backdated atau berasal dari input user, perlu `created_at` agar audit tidak ambigu.

### 4.5 Deleted / Voided / Reversed / Closed / Reopened At

Kolom lifecycle seperti `deleted_at`, `voided_at`, `closed_at`, `reopened_at`, dan future `reversed_at` adalah action timestamp.

Rule:

- Harus ditemani actor/reason jika memengaruhi finance, stok, atau akses.
- Tidak menggantikan `created_at`.
- Untuk reversal domain, prefer explicit `reversed_at` atau `occurred_at` dibanding hanya `timestamps()`.

## 5. Classification From Current Migration Audit

### 5.1 Complete Enough: Business/Action Date + System Timestamp

Table dalam kategori ini sudah relatif aman karena punya tanggal bisnis/aksi dan tanggal pencatatan sistem.

- `employee_debt_payments`
  - action/business: `payment_date`
  - system: `timestamps()`

- `payroll_disbursements`
  - action/business: `disbursement_date`
  - system: `timestamps()`

- `operational_expenses`
  - business: `expense_date`
  - system: `timestamps()`

- `inventory_cost_adjustments`
  - business: `tanggal_penyesuaian`
  - system: `created_at`

- `note_revisions`
  - business: `transaction_date`
  - system: `created_at`, `updated_at`

- `note_revision_surplus_dispositions`
  - action: `occurred_at`
  - system: `created_at`, `updated_at`

- `note_revision_surplus_refund_payments`
  - business: `effective_date`
  - action: `occurred_at`
  - system: `created_at`, `updated_at`

- `push_subscriptions`
  - action: `last_seen_at`
  - system: `timestamps()`

- `mobile_api_tokens`
  - action/lifecycle: `last_used_at`, `expires_at`, `revoked_at`
  - system: `timestamps()`

### 5.2 Action/Business Date Only

Table dalam kategori ini punya tanggal bisnis/aksi, tetapi tidak punya timestamp pencatatan row di table yang sama.

High-risk finance/source-of-truth candidates:

- `notes`
  - has: `transaction_date`, `closed_at`, `reopened_at`
  - missing: `created_at`, `updated_at`

- `customer_payments`
  - has: `paid_at`
  - missing: `created_at`, `updated_at`

- `customer_refunds`
  - has: `refunded_at`
  - missing: `created_at`, `updated_at`

- `supplier_invoices`
  - has: `tanggal_pengiriman`, `jatuh_tempo`, `voided_at`
  - missing: `created_at`, `updated_at`

- `supplier_receipts`
  - has: `tanggal_terima`
  - missing: `created_at`

- `supplier_payments`
  - has: `paid_at`
  - missing: `created_at`

- `inventory_movements`
  - has: `tanggal_mutasi`
  - missing: `created_at`

Other action/event candidates:

- `note_mutation_events`
  - has: `occurred_at`
  - missing: `created_at`

- `audit_events`
  - has: `occurred_at`
  - missing: `created_at`

- `supplier_payment_proof_attachments`
  - has: `uploaded_at`
  - missing: `created_at`

### 5.3 System Timestamp Only

Table dalam kategori ini punya timestamp sistem tetapi tidak punya tanggal aksi eksplisit.

- `employees`
  - has: `timestamps()`
  - later lifecycle columns: `started_at`, `ended_at`

- `employee_debts`
  - has: `timestamps()`
  - missing: explicit `debt_date` or `occurred_at`

- `expense_categories`
  - has: `timestamps()`
  - low risk as master/category table

- `employee_debt_adjustments`
  - has: `timestamps()`
  - missing: explicit `adjusted_at` or `occurred_at`

- `payroll_disbursement_reversals`
  - has: `timestamps()`
  - missing: explicit `reversed_at` or `occurred_at`

- `employee_debt_payment_reversals`
  - has: `timestamps()`
  - missing: explicit `reversed_at` or `occurred_at`

- `supplier_receipt_reversals`
  - has: `timestamps()`
  - missing: explicit `reversed_at` or `occurred_at`

- `supplier_payment_reversals`
  - has: `timestamps()`
  - missing: explicit `reversed_at` or `occurred_at`

- `transaction_workspace_drafts`
  - has: `created_at`, `updated_at`
  - low risk because draft/workspace state, not final ledger

- `note_revision_settlements`
  - has: `created_at`, `updated_at`
  - missing: explicit settlement event time if settlement is treated as domain event

### 5.4 Version/Edit History Tables

Current version tables use `changed_at` as edit/event timestamp.

- `product_versions`
- `supplier_versions`
- `supplier_invoice_versions`
- `employee_versions`

Current behavior:

- has: `changed_at`
- has: actor/reason/snapshot pattern
- missing: separate `created_at`

Decision:

- Acceptable if `changed_at` is always server-generated and immutable.
- Add `created_at` only if version rows may be inserted later for historical reconstruction or if `changed_at` can be user-supplied/backdated.

### 5.5 Projection Tables

Projection/read model tables should not be treated as canonical audit source.

Examples:

- `supplier_invoice_list_projection`
  - business: `shipment_date`, `due_date`, `voided_at`
  - projection: `projected_at`

- `note_history_projection`
  - business: `transaction_date`
  - projection: `projected_at`

- `supplier_list_projection`
  - business: `last_shipment_date`
  - projection: `projected_at`

Rule:

- Projection tables may use `projected_at`.
- Do not add audit semantics to projection tables unless required for debugging projection lag.
- Source-of-truth tables and audit/event tables remain canonical.

### 5.6 No Date/Timestamp Matches

The following migration files had no match against the temporal audit pattern.

These files must be reviewed by domain priority before any schema patch:

#### Low-risk technical/cache/index/foreign-key migrations

- `0001_01_01_000001_create_cache_table.php`
- `2026_04_06_210000_add_v2_hot_path_indexes_for_existing_tables.php`
- `2026_04_06_220100_add_v2_procurement_inventory_foreign_keys.php`
- `2026_04_06_220200_add_v2_transaction_finance_foreign_keys.php`
- `2026_04_06_220300_add_v2_note_mutation_workspace_foreign_keys.php`
- `2026_04_07_160200_rename_product_active_unique_indexes_to_legacy_names.php`
- `2026_04_18_235900_add_unique_product_per_revision_to_supplier_invoice_lines.php`
- `2026_04_22_000003_add_current_revision_pointer_to_notes_table.php`

#### Access / capability state

- `2026_03_10_000100_create_actor_accesses_table.php`
- `2026_03_10_000200_create_admin_transaction_capability_states_table.php`
- `2026_03_16_000100_create_admin_cashier_area_access_states_table.php`

Decision:

- These should generally have `created_at` and possibly `updated_at` if access state changes are persisted in-place.
- If access changes are only audited elsewhere, verify audit coverage before adding columns.

#### Master data

- `2026_03_11_000100_create_products_table.php`
- `2026_03_12_000100_create_suppliers_table.php`

Decision:

- Products and suppliers already have version tables later.
- Main tables may not strictly need `created_at/updated_at` if version history is canonical and complete.
- However, for operational list sorting/debugging, `created_at/updated_at` remains useful.
- Do not patch until current writer/version behavior is verified.

#### Procurement line / inventory state

- `2026_03_12_000300_create_supplier_invoice_lines_table.php`
- `2026_03_12_000500_create_supplier_receipt_lines_table.php`
- `2026_03_12_000700_create_product_inventory_table.php`
- `2026_03_13_000100_create_product_inventory_costing_table.php`
- `2026_04_18_000200_alter_supplier_receipt_lines_add_snapshots.php`

Decision:

- Line tables can inherit effective date from parent invoice/receipt.
- Inventory current-state tables usually do not need business date, but `updated_at` can help operational debugging.
- Source-of-truth stock movement date belongs in `inventory_movements`, not current-state inventory tables.
- Do not add date columns to line tables unless reporting or correction audit requires line-level effective dates.

#### Note/work item/payment allocation line-level tables

- `2026_03_14_000200_create_work_items_table.php`
- `2026_03_14_000300_create_work_item_service_details_table.php`
- `2026_03_14_000400_create_work_item_external_purchase_lines_table.php`
- `2026_03_14_000500_create_work_item_store_stock_lines_table.php`
- `2026_03_14_000700_create_payment_allocations_table.php`
- `2026_04_02_000800_create_payment_component_allocations_table.php`
- `2026_04_02_000900_create_refund_component_allocations_table.php`

Decision:

- Work item and line tables may inherit date from note/revision parent.
- Allocation tables may inherit date from payment/refund parent.
- If allocations are immutable ledger rows, they should at minimum have parent link and source payment/refund timestamp.
- Add `created_at` only if allocation creation time is needed independently for race/debug/audit.

#### Product stock threshold patch

- `2026_04_17_013500_add_stock_threshold_columns_to_products_table.php`

Decision:

- No temporal column required unless threshold changes need direct history.
- Product version table should be source of truth for threshold edit history if thresholds are included in snapshot.

## 6. Target Standard For New Migrations

### 6.1 Transaction / Finance Source-of-Truth Table

Required:

- business/effective date column, using existing domain term when already locked;
- `created_at`;
- `updated_at` only if mutable;
- actor/reason columns or audit event link if action affects money, stock, or lifecycle.

Preferred shape:

- `effective_date` for report date when no domain term exists;
- `occurred_at` for action/event time;
- `created_at` for row persistence time;
- `updated_at` only if row can change.

### 6.2 Immutable Ledger / Allocation Table

Required:

- parent/source id;
- amount fields;
- `created_at` if row creation time matters independently;
- no `updated_at` unless mutation is allowed.

Preferred:

- immutable rows;
- correction via new event/reversal row;
- no destructive update.

### 6.3 Version / History Table

Required:

- aggregate id;
- revision number;
- event name;
- actor;
- reason;
- `changed_at`;
- snapshot payload.

Optional:

- `created_at`, only when `changed_at` can differ from insert time.

### 6.4 Audit Event Table

Required:

- `occurred_at`;
- actor/context/aggregate metadata;
- snapshot table if needed.

Optional:

- `created_at`, if event ingestion time can differ from occurred time.

Current `audit_events` has `occurred_at` but no `created_at`. This is acceptable only if event creation is synchronous and never backfilled. If future import/backfill exists, add `created_at`.

### 6.5 Projection Table

Required:

- domain read fields;
- `projected_at`.

Do not use projection timestamp as source-of-truth business timestamp.

## 7. Priority For Future Schema Hardening

### P0 - Finance and stock source-of-truth ambiguity

Review first:

- `notes`
- `customer_payments`
- `customer_refunds`
- `supplier_invoices`
- `supplier_payments`
- `supplier_receipts`
- `inventory_movements`

Reason:

- affects cash, stock, receivable/payable, refund, COGS, report period, and correction audit.

### P1 - Reversal / adjustment event clarity

Review second:

- `employee_debt_adjustments`
- `payroll_disbursement_reversals`
- `employee_debt_payment_reversals`
- `supplier_receipt_reversals`
- `supplier_payment_reversals`

Reason:

- these represent domain actions but currently often rely only on `timestamps()`.

Recommended explicit column:

- `occurred_at` or domain-specific `reversed_at` / `adjusted_at`.

### P2 - Master/access/debuggability

Review third:

- `products`
- `suppliers`
- `actor_accesses`
- `admin_transaction_capability_states`
- `admin_cashier_area_access_states`
- current-state inventory/costing tables.

Reason:

- useful for operational traceability, but lower risk if version/audit tables are reliable.

## 8. Migration Patch Policy

Do not add timestamp columns blindly.

Before patching any table:

1. identify whether table is source-of-truth, read model, line table, allocation table, state table, or version table;
2. verify current writer code;
3. verify current domain date source;
4. verify whether backdated input is allowed;
5. verify whether report queries use business date or system timestamp;
6. add characterization tests before changing behavior;
7. backfill existing rows deterministically.

Backfill policy:

- if business date exists but `created_at` is added, do not automatically copy business date into `created_at` unless ADR approves;
- prefer safe fallback such as migration execution time for technical `created_at`, or explicit one-time backfill ADR;
- if exact historical created time is unknowable, mark it as a known audit gap.

## 9. Recommended Column Naming

Use existing locked domain terms when already present.

Preferred new names:

- `occurred_at` for domain event/action time;
- `effective_date` for report/effective date;
- `created_at` for row insert time;
- `updated_at` for row update time;
- `reversed_at` only when reversal is a first-class domain lifecycle event;
- `adjusted_at` only when adjustment is a first-class domain lifecycle event.

Avoid adding multiple names for the same meaning inside the same bounded context.

## 10. Explicit Non-Decisions

This blueprint does not approve any migration patch yet.

This blueprint does not require adding `timestamps()` to every table.

This blueprint does not redefine existing domain terms such as:

- `transaction_date`
- `paid_at`
- `refunded_at`
- `tanggal_pengiriman`
- `tanggal_terima`
- `tanggal_mutasi`

This blueprint does not treat projection tables as canonical audit source.

## 11. Next Safe Step

Create a DB temporal audit matrix document or script output with these columns:

- migration file
- table name
- category
- business/effective date columns
- action/event date columns
- system timestamp columns
- actor/reason columns
- source-of-truth risk level
- recommended action
- patch allowed now: yes/no
- required proof before patch

Recommended next command:

`rg -n --no-heading "Schema::create|Schema::table|date(Time)?\(|timestamp\(|timestamps\(|created_at|updated_at|occurred_at|changed_at|paid_at|refunded_at|effective_date|transaction_date|tanggal_|payment_date|disbursement_date|expense_date|deleted_at|voided_at|closed_at|reopened_at|started_at|ended_at|uploaded_at|projected_at|last_seen_at|last_used_at|expires_at|revoked_at|superseded_at|failed_at|email_verified_at" database/migrations | sort`

The next implementation step must stay at audit/matrix level before any schema modification.
