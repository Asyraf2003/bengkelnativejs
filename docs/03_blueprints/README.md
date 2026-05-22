# 03_blueprints

Design blueprints, DoD, and workflow for each implementation topic.

## Structure

Each topic subfolder contains three adjacent file types:

| Suffix | Type | Contents |
|---|---|---|
| `NNNN_topic_name.md` | Blueprint | Owner decisions, scope, access model, policy design |
| `NNNN_topic_name_dod.md` | DoD | Kriteria selesai — planning dan implementation |
| `NNNN_topic_name_workflow.md` | Workflow | Test matrix, implementation order, CLI workflow, commands |

## Suitable For

- mapping the active scope before implementation
- storing designs that are still allowed to change
- defining the work order and the proof that is required
- making the DoD explicit so completion is unambiguous

## Not Suitable For

- permanent decisions that should become ADR
- daily session notes
- final test results that fit better in a handoff
- old history that is already finished

## Subfolders

| Folder | Topic |
|---|---|
| `security/` | ADR-0019 access boundary, ADR-0020 public surface, ADR-0022 payment concurrency, ADR-0023 seeder safety |
| `finance/` | Note finance stabilization, finance residual, note revision refund ledger |
| `reporting/` | Report export, reporting execution workflow |
| `seeder/` | Legacy-to-clean seeder migration |
| `mobile/` | Mobile API v1 |
| `error_log_remediation/` | Error-log remediation process |
| `feature_continuation/` | Feature continuation scope blueprint |
