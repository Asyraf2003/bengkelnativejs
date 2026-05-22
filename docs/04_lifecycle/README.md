# 04_lifecycle

Runtime records: the operational trace of the system as it evolves.

## Subfolders

| Folder | Purpose |
|---|---|
| `error_log/` | Individual bug and security findings; one issue per file. |
| `handoff/` | Session recovery notes for the active session. Naming: `NNNN_topic_handoff.md`. |

## Suitable For

- `error_log/` for bugs or security findings that must be tracked to completion
- `handoff/` for session progress, proof, changed files, blockers, and next step

## Not Suitable For

- permanent decisions
- active blueprints
- completed legacy documents

## Note

If a handoff is no longer relevant for active work, move it to `docs/99_archive/handoff/`.

## Rules

- `error_log/` may not be deleted or have its status changed without proof and owner acceptance.
- `handoff/` is only for the latest session; once it is complete, move it to `docs/99_archive/handoff/`.
