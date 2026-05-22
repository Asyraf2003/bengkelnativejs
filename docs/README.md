# Hyperpos Documentation Index

## Purpose

This directory contains Hyperpos technical documentation: AI working rules, architecture decisions, implementation blueprints, lifecycle records, and audits.

The purpose of this index is to provide a clear reading path so humans and AI agents can find the right document without traversing the entire tree.

## Initial Reading Order

Read in this order:

    docs/01_standards/0007_ai_usage_guide.md
    docs/01_standards/0001_index.md
    The relevant active blueprint
    The latest local output from the operator

## Source Of Truth Priority

Use this order when documents conflict:

1. Latest local output from the operator (highest priority)
2. `docs/01_standards`
3. `docs/02_architecture/adr`
4. The active blueprint in `docs/03_blueprints`
5. The latest handoff in `docs/04_lifecycle/handoff`
6. `docs/99_archive`
7. General model knowledge (terendah)

## Placement Guide

Use this map to place documents in the right location:

| Document type | Location | Contents |
|---|---|---|
| Standards / mandatory rules | `docs/01_standards` | Global AI rules, decision policy, output rules, domain map, and stack rules |
| ADR / permanent decisions | `docs/02_architecture/adr` | Architecture, domain, lifecycle, reporting, and data-representation decisions |
| Blueprint / active design | `docs/03_blueprints` | Scope, design, DoD, workflow, test matrix, and implementation order |
| Error log / finding | `docs/04_lifecycle/error_log` | Bugs, security findings, and lifecycle issues; one issue per file |
| Active handoff | `docs/04_lifecycle/handoff` | Latest progress, proof, changed files, blockers, and next step |
| Audit report | `docs/05_audits` | Standalone audit reports, proof summaries, coverage, and findings |
| Legacy / historical | `docs/99_archive` | Old handoffs, old blueprints, old standards, and superseded documents |

If unsure, follow this order:

1. Permanent decisions go into ADR.
2. Designs still in progress go into blueprints.
3. Session work output goes into the active handoff.
4. Old history that is no longer active goes into the archive.

## Directory Map

### `docs/01_standards`

Mandatory rules for every AI session in this repo.

Use for: zero assumption rule, blueprint-first rule, one active step rule,
proof and progress rule, response structure, handoff policy, architecture boundary,
public contract protection, redaction rule, final domain map, and stack rules.

Not for: bug notes, feature status, commit hashes, or temporary local state.

### `docs/02_architecture/adr`

Permanent decision records. Sequential numbered `NNNN_snake_title.md`.

Use for: architecture decisions, domain decisions, lifecycle decisions,
reporting boundaries, and data representation.

If a decision changes: create a new ADR that supersedes the old one; do not edit the old ADR.

### `docs/03_blueprints`

Design blueprints, DoD, and workflow for each topic. Only for the active scope or the most recent scope still in progress.

Suitable content:

- scope in / scope out
- problem statement
- design options and design decisions
- DoD / test matrix / implementation order
- CLI workflow and execution order

Not for:

- permanent decisions that should become ADR
- daily session notes
- final test results that fit better in a handoff

Organized into subfolders:

- `security/` — ADR-0019 s/d ADR-0023 blueprints, DoD, workflow
- `finance/` — note finance, residual, revision-refund-ledger
- `reporting/` — report export, reporting execution
- `seeder/` — legacy-to-clean
- `mobile/` — mobile API
- `error_log_remediation/` — error log remediation docs
- `feature_continuation/` — feature continuation blueprint

Naming: `NNNN_topic_name.md` (blueprint), `NNNN_topic_name_dod.md` (DoD), `NNNN_topic_name_workflow.md` (Workflow).

### `docs/04_lifecycle`

Runtime records.

`error_log/` — individual bug and security findings, numbered `NNNN_snake_title.md`

`handoff/` — session recovery notes for the active or latest session

Handoffs are suitable for:

- progress summary
- proof and test output
- changed files
- blockers and risks
- the next session-opening prompt

Handoffs are not suitable for:

- permanent decisions
- active blueprints
- notes that are already clearly historical

When a session is finished, move the handoff to `docs/99_archive/handoff/`.

### `docs/05_audits`

Formal audit records with numbered snake_case filenames `NNNN_topic_name.md`.

Audits are suitable for:

- audit summaries
- coverage summaries
- proof of work
- recommendations and risks

An audit is not a replacement for a handoff or a blueprint.

### `docs/99_archive`

All legacy, superseded, and historical documents. Keep them as full, unmodified copies.

Do not store active work here. If something still needs to be worked on, keep it in `docs/03_blueprints` or `docs/04_lifecycle/handoff`.

- `standards/` — old standards docs
- `blueprints/` — blueprint v1
- `dod/` — v1 DoD
- `handoff/` — all old handoffs (step-based, UI, v2, Kotlin, etc.)

## Naming Pattern

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

## Promotion Rules

If a handoff contains a decision that must become permanent:

1. Create or update an ADR.
2. Reference the handoff as evidence.
3. Mark the handoff as historical.
4. Do not leave a permanent decision only in the handoff.
