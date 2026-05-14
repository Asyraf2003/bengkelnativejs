# DB Blueprint 0003 - DB Hardening Workflow

Status: Planning workflow  
Scope: Database hardening execution workflow for temporal audit, PostgreSQL readiness, CRUD readiness, and read path readiness  
Date: 2026-05-14  
Owner: HyperPOS

## 1. Purpose

Workflow ini menetapkan urutan kerja untuk memperbaiki kualitas database HyperPOS secara bertahap tanpa membuat patch yang saling memutar balik.

Workflow ini adalah prosedur eksekusi dari:

- `docs/03_blueprints/db/0001_temporal_audit_columns_blueprint.md`
- `docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`

Tujuan akhirnya:

- Date and time semantics pada finance sensitive table menjadi jelas.
- Schema tetap MySQL compatible untuk kondisi sekarang.
- Schema baru dan patch lama tetap PostgreSQL ready.
- CRUD dan read path utama bisa diarahkan ke target under 1 second tanpa index asal-asalan.
- Patch dilakukan dari root dependency agar perbaikan berikutnya tidak membatalkan perbaikan sebelumnya.

## 2. Non Goals

Workflow ini tidak:

- Mengizinkan broad schema rewrite.
- Mengizinkan migrasi PostgreSQL.
- Mengizinkan implementasi Go API.
- Mengklaim performa CRUD sudah under 1 second.
- Mengklaim semua P0 table sudah aman.
- Mengizinkan patch schema sebelum audit matrix dan source proof cukup.
- Mengganti domain term yang sudah terkunci seperti `transaction_date`, `paid_at`, `refunded_at`, `tanggal_pengiriman`, `tanggal_terima`, atau `tanggal_mutasi`.

## 3. Source Priority

Keputusan kerja harus mengikuti prioritas ini:

1. Local command output dari operator.
2. `AI_RULES`.
3. ADR yang diterima, terutama ADR 0028.
4. Active DB blueprint.
5. Active DB workflow ini.
6. Source code saat ini.
7. Test output.
8. Older handoff atau archive hanya sebagai referensi.

Jika dokumen mengklaim aman tetapi source atau command output membuktikan gap, source dan command output menang.

## 4. Evidence Labels

Gunakan label ini di matrix, patch note, dan respons kerja:

- `FACT`: terbukti dari command output, source, migration, test, ADR, atau blueprint.
- `GAP`: belum terbukti, belum dicek, belum dites, atau masih butuh owner decision.
- `RISK`: risiko finance, stock, report, audit, PostgreSQL portability, CRUD performance, atau regression.
- `DECISION`: keputusan kerja yang boleh dijalankan.
- `PROOF`: output yang membuktikan perubahan atau status.
- `STOP`: kondisi yang menghentikan patch.
- `NEXT`: satu langkah aktif berikutnya.

## 5. Current Proven Baseline

Baseline awal dari command output operator pada 2026-05-14:

- Branch: `main`
- HEAD: `fa95bed5`

DB blueprint files:

- `docs/03_blueprints/db/0001_temporal_audit_columns_blueprint.md`
- `docs/03_blueprints/db/0002_mysql_postgresql_crud_readiness_blueprint.md`

P0 migration candidates yang sudah terbukti dari local command output:

- `database/migrations/2026_03_12_000200_create_supplier_invoices_table.php`
- `database/migrations/2026_03_12_000400_create_supplier_receipts_table.php`
- `database/migrations/2026_03_12_000600_create_inventory_movements_table.php`
- `database/migrations/2026_03_12_000800_create_supplier_payments_table.php`
- `database/migrations/2026_03_14_000100_create_notes_table.php`
- `database/migrations/2026_03_14_000600_create_customer_payments_table.php`
- `database/migrations/2026_03_14_000700_create_payment_allocations_table.php`
- `database/migrations/2026_03_15_000100_create_customer_refunds_table.php`
- `database/migrations/2026_04_02_000800_create_payment_component_allocations_table.php`
- `database/migrations/2026_04_02_000900_create_refund_component_allocations_table.php`
- `database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php`
- `database/migrations/2026_04_27_000100_add_due_date_to_notes_table.php`
- `database/migrations/2026_04_27_000700_add_payment_method_and_cash_details_to_customer_payments.php`

Initial proven notes facts:

- `notes` has `transaction_date`, `note_state`, `closed_at`, `closed_by_actor_id`, `reopened_at`, `reopened_by_actor_id`, and `total_rupiah`.
- `notes` has indexes on `transaction_date`, `customer_name`, `note_state`, and `closed_at`.
- `notes` does not currently have `created_at` or `updated_at` in the base create migration.
- `DatabaseNoteWriterAdapter` currently writes create, header update, total update, and operational state update without `created_at` or `updated_at`.
- `V2NoteOperationalStateMigrationTest` inserts directly into `notes` without timestamp columns, so a `NOT NULL` timestamp patch without default or backfill can break existing tests and fixtures.

## 6. Dependency Order

DB hardening must run in this order.

### Step 0 - Baseline Intake

Collect current proof before any file change:

- Branch and HEAD.
- Git status.
- DB blueprint files.
- DB ADR reference.
- Relevant migration list.
- Relevant writer adapters.
- Relevant reader, query, and report paths.
- Relevant tests.

Gate:

- Active scope is explicit.
- Files to inspect are listed.
- No schema or source patch has started.

Stop condition:

- Branch or HEAD missing.
- Working tree dirty in unrelated files.
- Blueprint or ADR path missing.
- Migration list incomplete.

### Step 1 - Build DB Audit Matrix

Create or update a DB audit matrix before schema patch.

Matrix columns:

- Migration file.
- Table name.
- Category.
- Source of truth status.
- Business or effective date columns.
- Occurred or action date columns.
- System timestamp columns.
- Money columns.
- Status columns.
- Actor, reason, or audit link.
- Source id columns.
- JSON usage.
- FK and delete policy.
- Indexes.
- Known read path.
- PostgreSQL risk.
- CRUD performance risk.
- Recommendation.
- Patch allowed now: yes or no.
- Required proof before patch.

Gate:

- Each P0 table has a row.
- Each row separates `FACT` from `GAP`.
- Patch ordering is based on dependency, not convenience.

Stop condition:

- Any P0 table is missing from matrix.
- Recommendation is not tied to source proof.
- Patch allowed is yes without writer and read path proof.

### Step 2 - Pick One Root Table Group

Start from the table group that reduces downstream rework.

Default first group:

- `notes`

Reason:

- `notes` is transaction header and root for customer transaction flow.
- Payments, refunds, allocations, revisions, projections, and reports depend on note semantics.
- Fixing child tables before root date semantics can force rework.

Gate:

- Owner has accepted the active table group.
- Source files and tests for that group are identified.
- No unrelated table is patched.

Stop condition:

- The chosen table depends on an unresolved upstream table.
- Patch would change report basis without explicit report proof.

### Step 3 - Source Inspection For Active Group

For the active group, inspect:

- Create and alter migrations.
- Writer adapters.
- Reader adapters.
- Query, report, and projection paths.
- Direct DB test fixtures.
- Feature and unit tests that insert rows manually.
- Foreign key and delete policy migrations.
- Audit or event writer linkage.

For `notes`, inspect at minimum:

- `database/migrations/2026_03_14_000100_create_notes_table.php`
- `database/migrations/2026_04_22_000003_add_current_revision_pointer_to_notes_table.php`
- `database/migrations/2026_04_27_000100_add_due_date_to_notes_table.php`
- `app/Adapters/Out/Note/DatabaseNoteWriterAdapter.php`
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Adapters/Out/Note/Mappers/NoteMapper.php`
- `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php`
- `tests/Support/SeedsMinimalNotePaymentFixture.php`

Gate:

- Current behavior is documented.
- Writer behavior is documented.
- Direct insert fixtures are identified.
- Test blast radius is identified.

Stop condition:

- Source and docs contradict and contradiction is unresolved.
- Insert or update path is not known.
- Backfill behavior is not defined.

### Step 4 - Patch Blueprint For Active Group

Before source patch, write a narrow patch blueprint for the active table group.

Required fields:

- Table group.
- Exact problem.
- Current proven schema.
- Current proven writer behavior.
- Current proven tests and fixtures affected.
- Recommended schema change.
- Backfill policy.
- Domain impact.
- Report impact.
- PostgreSQL readiness impact.
- CRUD and read path impact.
- Files to touch.
- Files not to touch.
- RED or characterization proof plan.
- GREEN and focused proof plan.
- Rollback or defer criteria.

Gate:

- Patch blueprint is small enough for one implementation slice.
- No domain term is renamed.
- No report semantics are changed silently.

Stop condition:

- Patch blueprint requires broad rewrite.
- Backfill would invent historical facts.
- Report basis changes without report proof.

### Step 5 - Characterization Test

Add or update the smallest test that proves the current gap.

Preferred proof types:

- Migration column existence test.
- Direct insert compatibility test.
- Writer timestamp behavior test.
- Query or read path test.
- Focused projection or report regression test when report behavior is touched.

RED proof is required before patch unless the source is already patched. If RED is impossible, record why and use post patch characterization with explicit `GAP`.

Gate:

- Failure proves the intended gap.
- Failure is not caused by unrelated setup.
- Test name states the behavior.

Stop condition:

- Test is broad and fails for unrelated reasons.
- Test encodes an assumption not backed by blueprint or ADR.

### Step 6 - Minimal Schema And Application Patch

Patch only the active group.

Rules:

- Create new migration, do not edit old migration unless explicitly allowed.
- Preserve existing domain terms.
- Do not use MySQL enum for domain status.
- Do not use float or decimal for rupiah truth.
- Do not make JSON the only source for financial facts.
- Do not cascade delete financial history.
- Avoid PostgreSQL-hostile assumptions.
- Avoid index changes unless tied to a known read path.
- Keep source patch inside correct adapter or application boundary.

For `notes` timestamp hardening, default candidate shape:

- Add `created_at` as row created system timestamp with safe default and backfill behavior.
- Add `updated_at` only if update writer behavior is patched and tested.
- Keep `transaction_date` as business and report date.
- Keep `closed_at` and `reopened_at` as lifecycle action timestamps.
- Do not expose new timestamps to the domain object unless a use case needs them.
- Do not use `created_at` as report period.

Gate:

- Patch matches patch blueprint.
- Direct insert compatibility is preserved or intentionally updated with proof.
- Writer behavior is deterministic enough for tests.
- No unrelated files are touched.

Stop condition:

- Patch requires touching reports, UI, payment, refund, supplier, and inventory at once.
- Patch depends on unverifiable historical created time.
- Patch causes fixture churn without value.

### Step 7 - Targeted And Focused Verification

Run proof in layers.

Minimum:

- Syntax for changed PHP files.
- Targeted migration or database test.
- Targeted writer or use case test if writer changed.
- Focused blast radius around the active table group.
- Git diff check.

For `notes`, likely focused candidates:

- `tests/Feature/Database/V2NoteOperationalStateMigrationTest.php`
- Note create and update feature tests.
- Payment tests that seed notes directly.
- Refund tests that seed notes directly.
- Note revision tests that depend on note root.
- Report tests that read `transaction_date`.

Gate:

- Targeted tests pass.
- Focused blast radius passes.
- Failures are either fixed or explicitly deferred with owner acceptance.

Stop condition:

- Unrelated failures are not understood.
- Full suite failure is hidden.
- Docs claim verified beyond available proof.

### Step 8 - Docs Alignment

Update docs only after proof.

Required docs update:

- Active DB audit matrix row.
- Active DB workflow note if workflow changed.
- Active blueprint if the rule changed.
- ADR only if architecture decision changed.

Docs must record:

- Production files changed.
- Tests run.
- Exact pass or fail counts.
- Remaining gaps.
- Non goals.
- Next table group.

Gate:

- Docs do not overclaim.
- Docs distinguish fixed, verified, residual gap, and deferred.
- Local command output is cited or pasted in session or handoff.

Stop condition:

- Docs say fixed without proof.
- Docs erase gaps without test.
- Docs imply PostgreSQL migration has started.

### Step 9 - Move To Next Table Group

Only move after active group reaches one of:

- Targeted verified.
- Focused verified.
- Deferred with owner acceptance.

Default next groups after `notes`:

1. `customer_payments` and `customer_refunds`.
2. `payment_allocations`, `payment_component_allocations`, and `refund_component_allocations`.
3. `supplier_invoices`, `supplier_receipts`, and `supplier_payments`.
4. `inventory_movements`.
5. P1 reversal and adjustment tables.
6. P2 master, access, and current state tables.
7. CRUD, index, and read path hardening.

Do not start CRUD or index hardening before temporal dan source of truth ambiguity is mapped.

## 7. Patch Ordering Rules

Use these rules when deciding where to start:

1. Root transaction header before child financial rows.
2. Source of truth table before projection or read model.
3. Business, action, and system date semantics before index optimization.
4. Writer contract before report or dashboard contract.
5. Audit and reversal path before destructive update behavior.
6. Matrix before schema patch.
7. RED or characterization proof before GREEN patch.
8. Focused proof before docs closure.

## 8. Status Model

Use these statuses in the matrix:

| Status | Meaning |
| --- | --- |
| Reported | Gap or target has been identified. |
| Audited | Source and current behavior have been inspected. |
| Patch Blueprinted | Narrow patch plan exists with scope and proof plan. |
| Characterized RED | Test or characterization proves current gap. |
| Patched Unverified | Source patch exists but proof is incomplete. |
| Targeted Verified | Targeted proof passes for the active gap. |
| Focused Verified | Focused blast-radius proof passes. |
| Docs Aligned | Docs reflect source, proof, and remaining gaps. |
| Deferred with owner acceptance | Scope is intentionally deferred with explicit acceptance. |

Do not use `Fixed` unless source patch, tests, and docs alignment proof exist for the scope being claimed.

## 9. First Recommended Active Slice

Recommended first active slice:

- Table group: `notes`
- Problem: root transaction header has business and action timestamps but lacks system row timestamps.
- Reason: downstream payment, refund, allocation, revision, and report behavior depends on `notes` semantics.
- First deliverable: DB audit matrix row and patch blueprint for `notes`.
- First patch: not approved until writer, fixtures, migration behavior, and backfill policy are verified.

Initial recommended notes decision:

- `transaction_date` remains business and report date.
- `closed_at` remains lifecycle close action time.
- `reopened_at` remains lifecycle reopen action time.
- `created_at` should represent system row creation time if added.
- `updated_at` should represent system row mutation time if added.
- Unknown historical created time must not be invented from `transaction_date` unless a separate ADR or owner decision accepts that approximation.

## 10. Completion Criteria For This Workflow

DB hardening workflow is complete only when:

- All P0 rows have matrix entries.
- Active P0 table groups are either verified or explicitly deferred.
- Temporal audit gaps are closed or documented.
- PostgreSQL readiness risks are closed or documented.
- CRUD dan index risks are tied to real read paths.
- Docs and source do not contradict.
- No fix is claimed without command proof.

## 11. Next Safe Step

Create the DB audit matrix for P0 tables.

Do not patch `notes` yet.

Start with the `notes` matrix row because it is the root transaction header and reduces downstream rework risk.

## 12. Handoff Archive

DB hardening handoff archive:

- [DB hardening handoff folder](../../99_archive/handoff/db/)
- [Current DB hardening handoff](../../99_archive/handoff/db/0001_db_hardening_notes_payment_refund_handoff.md)
