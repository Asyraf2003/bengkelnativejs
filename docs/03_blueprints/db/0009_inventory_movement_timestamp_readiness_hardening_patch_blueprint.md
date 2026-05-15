# DB Blueprint 0009 - Inventory Movement Timestamp Readiness Hardening Patch Blueprint

Status: Patch Blueprinted
Scope: `inventory_movements` row timestamp/readiness hardening
Owner: HyperPOS

## 1. Active Table Group

Table group:

- `inventory_movements`

Category:

- Ledger/movement table

Source-of-truth status:

- P0 stock movement source

Adjacent projection tables observed but not patched in this slice:

- `product_inventory`
- `product_inventory_costing`

## 2. Exact Problem

`inventory_movements` is a P0 stock movement ledger/source table.

Current proven gap:

- `inventory_movements.created_at` is not present in the root migration.
- `inventory_movements.updated_at` is not present in the root migration.
- Current writer behavior is not yet proven to write system row timestamps.
- Matrix row for `inventory_movements` is still `Reported` with audit fields marked as `GAP`.
- `product_inventory` and `product_inventory_costing` exist and are heavily used, but they do not yet have dedicated matrix rows.

Risk:

- Adding non-null timestamps without nullable compatibility can break direct inserts across tests and seeders.
- Treating row timestamps as mutation/report dates would corrupt inventory reporting semantics.
- Changing stock movement math can break inventory quantity, costing, COGS, refund reversal, and operational profit reports.
- Changing projection table timestamp semantics without a separate decision can make `created_at` mean "projection rebuild time" instead of original stock state creation time.
- Adding timestamp indexes without read-path proof can create unnecessary index bloat.
- Changing FK/delete semantics can break stock/reporting assumptions.

## 3. Current Proven Schema

Inventory movement migration:

- `database/migrations/2026_03_12_000600_create_inventory_movements_table.php`

Proven `inventory_movements` columns:

- `id`
- `product_id`
- `movement_type`
- `source_type`
- `source_id`
- `tanggal_mutasi`
- `qty_delta`
- `unit_cost_rupiah`
- `total_cost_rupiah`

Proven indexes:

- `product_id`
- `source_type`, `source_id`
- `tanggal_mutasi`

Current proven gap:

- no `created_at`
- no `updated_at`

## 4. Current Proven Foreign Key Behavior

FK migration:

- `database/migrations/2026_04_06_220100_add_v2_procurement_inventory_foreign_keys.php`

Proven FK behavior:

- `inventory_movements.product_id` references `products.id` with `restrictOnDelete`.

Decision:

- Do not change FK/delete semantics in this timestamp patch.
- Current restrict-on-delete behavior is preserved.

## 5. Current Proven Usage

`inventory_movements` is used by:

- inventory movement writer/reader adapters
- inventory issue operation
- stock adjustment flow
- reverse issued inventory operation
- reverse note store-stock inventory operation
- rebuild inventory projection tests
- supplier/procurement load seeder
- customer transaction/load seeders
- inventory movement reporting
- inventory current snapshot reporting
- inventory reconciliation reporting
- dashboard inventory/operational performance reporting
- operational profit and COGS reporting
- note store-stock lifecycle tests
- refund reversal lifecycle tests

Adjacent projection tables are used by:

- `product_inventory` for current quantity snapshot
- `product_inventory_costing` for current average cost and inventory value snapshot

Known high-risk semantics:

- `tanggal_mutasi` is the stock movement/business/report date.
- `qty_delta` is quantity movement delta.
- `unit_cost_rupiah` and `total_cost_rupiah` are inventory/costing values.
- `source_type` and `source_id` connect movements to originating domain actions.
- `created_at` and `updated_at` must remain system row timestamps only.

## 6. Recommended Schema Change

Create a new migration. Do not edit old migrations.

Recommended first patch:

- Add nullable-safe/backfilled `created_at` to `inventory_movements`.
- Add nullable-safe/backfilled `updated_at` to `inventory_movements`.

Preferred semantics:

- `created_at`: system row creation/persistence timestamp.
- `updated_at`: system row mutation timestamp.
- For insert-only movement rows, initial `updated_at` equals `created_at`.

Do not add timestamp indexes in this slice.

Reason:

- Existing read paths use product, source lookup, and `tanggal_mutasi`.
- No proven read path currently filters/sorts inventory movement rows by `created_at` or `updated_at`.
- Index hardening must follow real read-path proof.

## 7. Backfill Policy

Do not infer inventory movement `created_at` from:

- `tanggal_mutasi`
- source transaction date
- supplier receipt date
- note date
- refund date
- reversal date
- revision order
- report period

Safe policy:

- Keep timestamp columns nullable to preserve direct insert compatibility.
- Backfill existing rows with migration execution time only if the migration explicitly updates existing rows.
- Record that historical creation time for pre-patch rows remains approximate/unknown.
- Writers must set `created_at` and `updated_at` for new rows going forward.

## 8. Domain And Report Impact

Expected domain impact:

- No change to stock movement semantics.
- No change to inventory quantity math.
- No change to average cost math.
- No change to COGS/profit reporting.
- No change to refund reversal behavior.
- No change to procurement receipt stock movement behavior.
- No change to source id/source type semantics.
- No change to report period semantics.
- No change to FK/delete semantics.

Forbidden impact:

- Do not use `created_at` or `updated_at` as inventory report date.
- Do not replace `tanggal_mutasi`.
- Do not change movement type semantics.
- Do not change source type/source id semantics.
- Do not change inventory issue/reversal math.
- Do not change supplier receipt stock movement logic.
- Do not change projection rebuild behavior.
- Do not patch `product_inventory` or `product_inventory_costing` in this slice without a separate projection timestamp decision.
- Do not add timestamp indexes without proven read-path demand.

## 9. PostgreSQL Readiness Impact

Patch must avoid:

- relying on implicit MySQL timestamp defaults as domain truth;
- unsigned-only assumptions;
- DB engine-specific timestamp side effects;
- non-portable index claims;
- report semantics tied to system timestamps.

Preferred:

- explicit application timestamp writes in inventory movement writer;
- nullable direct-insert compatibility until fixture migration is intentionally handled;
- no timestamp indexes without proven read-path demand.

## 10. Files To Touch In Patch Slice

Expected production files:

- new migration under `database/migrations/`
- `app/Adapters/Out/Inventory/DatabaseInventoryMovementWriterAdapter.php`

Expected test files:

- focused database schema test for `inventory_movements` timestamp columns
- focused inventory movement writer timestamp test
- focused inventory/reporting non-regression tests most likely to break

Docs:

- this blueprint
- `docs/03_blueprints/db/0004_db_audit_matrix.md`

Do not touch in this slice:

- allocation math
- refund math
- inventory quantity math
- average cost math
- COGS/profit report semantics
- UI
- API/mobile
- supplier payable math
- receipt stock movement logic
- proof attachment semantics
- reversal semantics
- FK/delete semantics
- timestamp indexes
- Go API
- PostgreSQL runtime

## 11. RED Proof Plan

First RED schema test must prove:

- `inventory_movements.created_at` is missing.
- `inventory_movements.updated_at` is missing.

First RED writer test must prove:

- movement rows written by `DatabaseInventoryMovementWriterAdapter` do not have system row timestamps, or are not proven to have them.

RED proof must be captured before patching migration/source.

## 12. GREEN Proof Plan

Minimum targeted GREEN proof:

- schema test passes for `inventory_movements.created_at` and `inventory_movements.updated_at`
- writer test proves new movement rows persist `created_at` and `updated_at`
- writer test proves direct domain values remain unchanged:
  - `tanggal_mutasi`
  - `qty_delta`
  - `unit_cost_rupiah`
  - `total_cost_rupiah`
  - `source_type`
  - `source_id`

Focused blast-radius proof should include inventory/reporting tests around:

- inventory issue
- inventory reversal
- inventory projection rebuild
- inventory movement summary
- inventory stock value report
- operational profit/COGS
- supplier/procurement stock receipt linkage if available in focused test set

Full/global verification remains deferred unless explicitly run.

## 13. Completion Criteria

This slice can be marked `Focused Verified` only after:

- RED schema proof is captured.
- RED writer proof is captured if current writer gap is testable.
- Migration/source patch is applied.
- Targeted GREEN schema and writer proof passes.
- Focused inventory/reporting blast-radius proof passes.
- Matrix row is updated with source, proof, residual gaps, and final status.
- Docs reflect remaining gaps explicitly.

Do not claim:

- full `make verify`
- browser/manual QA
- PostgreSQL runtime migration
- full DB hardening completion
