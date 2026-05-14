# DB Blueprint 0002 - MySQL, PostgreSQL, and CRUD Readiness

Status: Draft  
Scope: database schema discipline, CRUD performance, MySQL-to-PostgreSQL readiness  
Date: 2026-05-14  
Owner: HyperPOS  

## 1. Purpose

Blueprint ini menetapkan standar struktur database HyperPOS agar:

1. schema MySQL tetap nyaman dipakai sekarang,
2. CRUD utama bisa ditargetkan di bawah 1 detik,
3. schema tidak membuat jebakan migrasi ke PostgreSQL,
4. future API dapat memakai kontrak application use case yang sama,
5. finance, stock, refund, payment, supplier, note revision, dan reporting tetap audit-ready.

Blueprint ini melengkapi `docs/03_blueprints/db/0001_temporal_audit_columns_blueprint.md`.

## 2. Source of Truth

Source of truth aturan portability:

- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`

Prinsip dari ADR 0028:

- MySQL tetap aktif sekarang.
- PostgreSQL belum aktif.
- Go API belum aktif.
- Schema baru harus PostgreSQL-ready.
- Finance-sensitive table harus memisahkan business date, effective date, occurred_at, created_at, dan updated_at.
- Jangan memakai MySQL enum untuk status domain.
- Jangan memakai float/decimal untuk rupiah.
- Jangan menjadikan JSON sebagai satu-satunya financial truth.
- Jangan cascade delete financial history.
- Index harus mengikuti read path nyata.
- UI/API target di bawah 1 detik harus memakai projection atau indexed read model jika direct ledger read mulai berat.

## 3. Table Categories

Setiap migration baru harus dikategorikan.

### 3.1 Master Table

Contoh:

- `products`
- `suppliers`
- `employees`
- `expense_categories`

Rule:

- menyimpan current master state;
- boleh mutable;
- perubahan penting harus punya version/audit history;
- prefer `created_at` dan `updated_at` untuk operational debugging;
- status gunakan string, bukan enum;
- delete sensitif harus soft delete atau lifecycle field, bukan hard delete sembarang.

### 3.2 Transaction Header Table

Contoh:

- `notes`
- `supplier_invoices`

Rule:

- menyimpan root transaksi;
- wajib punya business date;
- finance-sensitive header harus punya actor/audit path;
- lifecycle action seperti close, void, reopen, refund harus eksplisit;
- future patch harus mempertimbangkan `created_at` dan `updated_at`.

### 3.3 Transaction Line Table

Contoh:

- `supplier_invoice_lines`
- `supplier_receipt_lines`
- `work_items`
- `work_item_service_details`
- `work_item_external_purchase_lines`
- `work_item_store_stock_lines`

Rule:

- line dapat mewarisi business date dari parent header;
- line tidak wajib punya date sendiri kecuali line-level correction/reporting butuh tanggal berbeda;
- line harus punya stable source id/root id bila dipakai untuk revision/correction;
- line money/qty harus explicit column, bukan hanya JSON.

### 3.4 Ledger / Movement Table

Contoh:

- `inventory_movements`
- future cash/balance ledger

Rule:

- immutable by default;
- correction dilakukan via reversal/new row, bukan update destruktif;
- wajib punya source id;
- wajib punya amount/qty explicit column;
- wajib punya business/effective date;
- recommended punya `created_at`;
- index mengikuti report and lookup path.

### 3.5 Allocation Table

Contoh:

- `payment_allocations`
- `payment_component_allocations`
- `refund_component_allocations`

Rule:

- allocation harus menunjuk source payment/refund dan target note/component;
- amount harus explicit integer;
- immutable by default;
- line date bisa diwarisi dari source payment/refund;
- `created_at` recommended jika allocation race/debug/audit perlu dibedakan dari payment date.

### 3.6 Event / Audit Table

Contoh:

- `audit_events`
- `note_mutation_events`

Rule:

- wajib punya `occurred_at`;
- wajib punya actor/context jika sensitive;
- snapshot boleh JSON;
- JSON tidak boleh menjadi satu-satunya source untuk money/status/source id/report-critical facts;
- `created_at` recommended jika future import/backfill/event ingestion bisa berbeda dari `occurred_at`.

### 3.7 Snapshot / Version Table

Contoh:

- `product_versions`
- `supplier_versions`
- `employee_versions`
- `supplier_invoice_versions`
- `audit_event_snapshots`

Rule:

- snapshot/history boleh menyimpan JSON payload;
- query-critical facts tetap explicit columns;
- version table wajib punya aggregate id, revision number, event name, actor/reason, and `changed_at`;
- `created_at` optional jika `changed_at` bisa berbeda dari insertion time.

### 3.8 Projection / Read Model Table

Contoh:

- `note_history_projection`
- `supplier_invoice_list_projection`
- `supplier_list_projection`

Rule:

- projection dipakai untuk UI/report speed;
- projection bukan canonical financial truth;
- wajib punya `projected_at`;
- index harus mengikuti UI default sort/filter/search;
- boleh denormalized selama source rebuild path jelas.

### 3.9 State Table

Contoh:

- access/capability/current-state inventory table

Rule:

- menyimpan current state;
- mutation harus auditable;
- `created_at`/`updated_at` recommended;
- jika state change finance/security sensitive, wajib punya audit event atau history table.

### 3.10 Technical Table

Contoh:

- cache
- jobs
- sessions
- tokens
- framework tables

Rule:

- mengikuti kebutuhan framework;
- tidak wajib mengikuti finance audit rule kecuali dipakai untuk security/audit domain.

## 4. CRUD Under 1 Second Readiness

### 4.1 Definition

Target CRUD < 1 detik berarti response server-side untuk halaman/action utama harus dirancang agar query dan mapping tidak membesar liar saat data tumbuh.

Target ini bukan klaim performa sampai ada proof dari test, query log, benchmark, atau production measurement.

### 4.2 Required Read Path Documentation

Setiap table yang dipakai halaman CRUD utama harus punya read path.

Minimal:

- default list query;
- detail query;
- search query;
- filter query;
- sort query;
- mutation lookup query;
- report query jika table dipakai laporan.

### 4.3 Index Policy

Index harus mengikuti read path nyata.

Allowed index types in current MySQL scope:

- single-column index untuk lookup/filter kuat;
- composite index untuk default list/filter/sort path;
- unique index untuk invariant;
- foreign key index jika DB tidak otomatis cukup.

Forbidden:

- index asal-asalan;
- index hanya karena kolom “kelihatan penting”;
- composite index tanpa query path;
- relying on projection without rebuild strategy.

### 4.4 Composite Index Order

Composite index order harus mengikuti query:

1. equality filters,
2. range/date filters,
3. sort columns,
4. stable tie-breaker id when needed.

Example:

- `note_root_id, status`
- `note_root_id, occurred_at`
- `status, effective_date`
- `lifecycle_status, tanggal_pengiriman`
- `voided_at, shipment_date, supplier_invoice_id`

### 4.5 Pagination Policy

List pages must not load unbounded rows.

Rule:

- use pagination for admin/cashier tables;
- avoid `get()` for large list screens;
- avoid full-table scan for search;
- use explicit projection/read model for heavy list/report paths;
- keep export separate from interactive list when dataset can be large.

### 4.6 N+1 Policy

Forbidden:

- per-row query in list page;
- per-line product/supplier/customer lookup without eager/preloaded mapping;
- report loop that queries detail row by row.

Required:

- batch query;
- join/projection;
- preloaded map;
- query count test for critical screens when practical.

### 4.7 Projection Policy

Use projection when direct normalized query becomes too heavy.

Projection must document:

- source tables;
- rebuild trigger;
- stale tolerance;
- `projected_at`;
- default index;
- fallback behavior if projection missing.

Projection is read-only from application perspective.

## 5. MySQL-to-PostgreSQL Readiness Rules

### 5.1 Identity

Use string ids for domain identity unless ADR says otherwise.

Do not expose auto-increment ids as public/domain contract.

### 5.2 Money

Store rupiah as integer.

Forbidden:

- float for rupiah;
- decimal as primary rupiah representation unless ADR approves;
- relying only on MySQL unsigned as invariant.

Validation must exist in domain/application when negative values are invalid.

### 5.3 Status

Use string status columns.

Forbidden:

- MySQL enum for domain state.

Allowed values must be documented in domain contract, ADR, DTO, or policy.

### 5.4 Date and Time

Follow DB Blueprint 0001.

Finance-sensitive tables must not blur:

- business/effective date;
- occurred/action time;
- created row time;
- updated row time.

### 5.5 JSON

JSON is allowed for:

- snapshot;
- metadata;
- compatibility payload;
- UI draft payload.

JSON must not be only source of truth for:

- money;
- lifecycle status;
- source ids;
- actor ids;
- business date;
- occurred_at;
- inventory quantity;
- payment/refund amount;
- customer balance amount;
- report-critical fields.

### 5.6 Foreign Key and Delete Policy

Financial history must use restrict delete when practical.

Forbidden:

- cascade delete financial history;
- nullable foreign key as lazy replacement for immutable snapshot;
- deleting source records that reports still depend on.

If FK cannot be strict due legacy compatibility, document the reason and test the behavior.

### 5.7 MySQL-Specific Feature Avoidance

Avoid creating new dependency on:

- MySQL enum;
- MySQL-only generated behavior without Postgres equivalent;
- unsigned-only invariants;
- JSON path truth as primary query source;
- implicit timestamp defaults that differ by database;
- engine/collation-specific behavior as business rule.

## 6. Migration Checklist

Every new finance-sensitive migration must document or prove:

- table category;
- table purpose;
- source of truth status;
- business/effective date columns;
- occurred/action columns;
- created_at/updated_at policy;
- money columns;
- status columns and allowed values;
- actor/reason/audit linkage;
- source id columns;
- foreign keys;
- delete policy;
- nullable policy;
- indexes and read paths;
- expected CRUD/list/report path;
- JSON usage;
- PostgreSQL concern;
- backfill strategy if altering existing data.

## 7. Priority Gaps From Current Audit

### P0

Review first because these affect finance, stock, or report truth:

- `notes`
- `customer_payments`
- `customer_refunds`
- `supplier_invoices`
- `supplier_payments`
- `supplier_receipts`
- `inventory_movements`
- `payment_allocations`
- `payment_component_allocations`
- `refund_component_allocations`

### P1

Review second because these affect reversal or adjustment audit:

- `employee_debt_adjustments`
- `payroll_disbursement_reversals`
- `employee_debt_payment_reversals`
- `supplier_receipt_reversals`
- `supplier_payment_reversals`

### P2

Review third because these affect master/access/debuggability:

- `products`
- `suppliers`
- `actor_accesses`
- `admin_transaction_capability_states`
- `admin_cashier_area_access_states`
- `product_inventory`
- `product_inventory_costing`

## 8. Non-Decisions

This blueprint does not approve broad schema rewrite.

This blueprint does not authorize PostgreSQL migration.

This blueprint does not authorize Go API implementation.

This blueprint does not claim CRUD is already under 1 second.

This blueprint does not require every table to have every timestamp.

This blueprint does not replace ADR 0028.

## 9. Next Safe Step

Create a DB audit matrix for current migrations with these columns:

- migration file;
- table name;
- category;
- source-of-truth status;
- business/effective date;
- occurred/action date;
- system timestamps;
- money columns;
- status columns;
- actor/reason/audit link;
- JSON usage;
- FK/delete policy;
- indexes;
- known read path;
- PostgreSQL risk;
- CRUD performance risk;
- recommendation;
- patch allowed now: yes/no.

No schema patch should be started until the matrix identifies one narrow table group and its read/write proof.
