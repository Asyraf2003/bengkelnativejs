# 01_standards

Mandatory rules for every AI session in this repo. Everything here is permanent and only changes when the owner explicitly decides to change it.

## Contents

| File / Folder | Purpose |
|---|---|
| `0001_index.md` | Entry point. Mandatory read order and module map. |
| `0002_decision_policy.md` | Decision hierarchy and rule priority. |
| `0003_gpt_bootstrap_prompt.md` | Bootstrap prompt for a new AI session. |
| `0004_session_start_protocol.md` | Session opening protocol. |
| `0005_handoff_template.md` | Canonical template for creating a new handoff. |
| `0006_final_review_checklist.md` | Checklist before closing a large session. |
| `0007_ai_usage_guide.md` | Which context belongs in which layer. |
| `0008_ai_personalization_profile.md` | Repo-level AI personalization profile from the owner. |
| `0099_changelog.md` | Change log for the AI_RULES package. |
| `core/` | Core principles: scope, blueprint-first, step-by-step, proof. |
| `workflow/` | Workflow rules: response, active step, handoff, capacity. |
| `output/` | Output format rules: file, markdown, Blade, terminal. |
| `architecture/` | Architecture rules: hexagonal, contracts, error, debug, audit. |
| `domain/` | Cashier domain contracts: domain map, UI terms, payment, reporting. |
| `stack/` | Stack rules: Laravel, Go, AWS. |

## Rules

Do not add a new file here unless it is a mandatory AI rule that applies to every session or the owner promotes it as canonical standards.
Topic-specific DoD, workflow, and blueprints live in `docs/03_blueprints/`.
