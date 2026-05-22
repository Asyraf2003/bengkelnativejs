# Central Documentation Map

Navigation map for the entire Hyperpos documentation set.

## Folder Structure (L1)

### [01_standards](./01_standards/)

Mandatory rules for every AI session. Static unless an explicit decision changes them.

- `core/` - core principles: scope, blueprint-first, step-by-step, proof
- `workflow/` - response structure, active step policy, handoff policy, session capacity
- `output/` - file delivery, markdown rule, blade rule, terminal delivery
- `architecture/` - hexagonal baseline, public contracts, error handling, debug gating, audit DoD
- `domain/` - final domain map, UI terms, payment lifecycle, reporting boundary
- `stack/` - Laravel rules, Go rules, AWS baseline

### [02_architecture](./02_architecture/)

Permanent decision records.

- `adr/` - ADR files. Naming: `NNNN_snake_title.md`. Sequential, not date-based.
  If a decision changes, create a new ADR that supersedes the old one and mark the old ADR as superseded.

### [03_blueprints](./03_blueprints/)

Design blueprints, DoD, and workflow by topic. Flat within each topic subfolder.

- `security/` - ADR-0019 through ADR-0023: access boundary, public surface, payment concurrency, seeder safety
- `finance/` - note finance stabilization, finance residual, note revision refund ledger
- `reporting/` - report export, reporting execution workflow
- `seeder/` - legacy-to-clean
- `mobile/` - mobile API
- `error_log_remediation/` - DoD, sequence, workflow, strict closure protocol
- `feature_continuation/` - feature continuation blueprint

File naming: `NNNN_topic_name.md` (blueprint), `NNNN_topic_name_dod.md` (DoD), `NNNN_topic_name_workflow.md` (workflow).

### [04_lifecycle](./04_lifecycle/)

Runtime records - ongoing, not historical.

- `error_log/` - bug and security findings. Naming: `NNNN_snake_title.md`
- `handoff/` - session recovery notes for the active session. Naming: `NNNN_topic_handoff.md`

### [05_audits](./05_audits/)

Formal audit records. Naming: `NNNN_topic_name.md`.

### [99_archive](./99_archive/)

All legacy, superseded, and historical material. Keep full copies and do not modify them.

- `standards/` - old standards (handoff-ai-rules-modular)
- `blueprints/` - blueprint v1, workflow v1
- `dod/` - v1 DoD
- `handoff/` - all old handoffs (step-based, UI, v2, Kotlin, mobile-api, seeder, error_log, codex-security)

---

## Naming Convention

| Type | Format | Example |
|---|---|---|
| ADR | `NNNN_snake_title.md` | `0019_note_access_boundary_cashier_date_window_and_transaction_capability_enforcement.md` |
| Blueprint | `NNNN_topic_name.md` | `0003_finance_residual.md` |
| DoD | `NNNN_topic_name_dod.md` | `0004_finance_residual_dod.md` |
| Workflow | `NNNN_topic_name_workflow.md` | `0005_finance_residual_workflow.md` |
| Error log | `NNNN_snake_title.md` | `0009_cashiers_can_rewrite_closed_paid_notes_via_workspace_update.md` |
| Audit record | `NNNN_topic_name.md` | `0002_error_log_solution_and_adr_coverage_summary.md` |
| Active handoff | `NNNN_topic_handoff.md` | `0001_scope_handoff.md` |
| Folder | `NN_prefix_snake_case` for L1, `snake_case` for subfolders | `01_standards/`, `error_log/` |

## Structural Change Log

- **2026-05-13**: Full reorganization to the standard hexagonal docs layout. Kebab-case was made consistent, topic-based blueprint subfolders were introduced, all legacy content moved to `99_archive`, path references were fixed, and duplicate content was removed.
- **2026-05-11**: Initial migration from flat legacy to hybrid L1.
