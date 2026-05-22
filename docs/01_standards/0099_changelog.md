# AI Standards Changelog

## 2026-05-12 - Standards Path Normalization

- Clarified `docs/01_standards` as the canonical standards root.
- Aligned bootstrap and usage guide references away from legacy root `AI_RULES` paths.
- Kept historical handoff content historical instead of rewriting old proof.
- Deferred file and folder renames until the backlink audit.

## 2026-04-26 - Session Capacity Policy

- Added `workflow/24-session-capacity-policy.md`.
- Required a capacity footer at the end of every technical work response.
- Added a below-80% threshold rule to stop large implementation work and prepare a handoff.
- Clarified that new sessions reset active chat clutter but do not imply perfect 100% capability.
- Updated index, session start protocol, and handoff policy references.

## 2026-03-26
- Created the initial modular `AI_RULES` structure.
- Added Decision Policy as the main conflict protocol.
- Added the core, workflow, output, architecture, cashier domain, and stack modules.
- Locked the domain map, UI terms, payment lifecycle, and reporting boundary into separate rules.

## Changelog Update Rules
- Every important rule change must add a new entry.
- If a change is triggered by an ADR or handoff, include the reference in the next update.

## 2026-03-26 - Harden Entrypoint and Core P0
- Strengthened `0001_index.md` into entrypoint enforcement.
- Strengthened `0002_decision_policy.md` with mandatory decision sequence, gap rule, forbidden shortcuts, and stop conditions.
- Strengthened `10-scope-and-facts.md` with classification and inference rules.
- Strengthened `11-blueprint-first.md` with the implementation gate.

## 2026-03-26 - Harden Execution and Workflow
- Strengthened `12-step-by-step-execution.md` with the validation gate and forbidden behavior.
- Strengthened `13-proof-and-progress.md` with accepted proof, progress rules, and the ban on claims without evidence.
- Strengthened `20-response-structure.md` as the default work response structure.
- Strengthened `21-active-step-policy.md` for one-active-step discipline.
- Strengthened `22-option-evaluation.md` so option evaluation stays contextual and includes pros / cons.
- Strengthened `23-handoff-policy.md` so another GPT can continue a slice without assumptions.

## 2026-03-26 - Harden Architecture, Domain, and Stack
- Strengthened `40-hexagonal-baseline.md` with source-of-truth rules and forbidden behavior.
- Strengthened `41-public-contracts.md` with a change gate for public contracts.
- Strengthened `42-error-handling-and-redaction.md` with the security principle and ban on raw leaks.
- Strengthened `43-debug-gating.md` and `44-audit-and-dod.md`.
- Strengthened `50-final-domain-map.md`, `51-ui-terms-and-status.md`, `52-payment-lifecycle.md`, and `53-reporting-boundary.md`.
- Strengthened the stack rules for Laravel, Go, and the AWS baseline.

## 2026-03-26 - Harden Output and Delivery
- Strengthened `30-file-delivery.md` so file delivery must name the exact path and provide the full final content.
- Strengthened `31-markdown-output-rule.md` so markdown output follows the one-code-block contract with an outer `text` fence.
- Strengthened `32-blade-rule.md` so Blade stays focused on presentation and avoids inline PHP blocks.
- Strengthened `33-terminal-command-delivery.md` so terminal command delivery is split into batches when needed and always has execution context and verification.

## 2026-03-26 - Add Bootstrap and Handoff Support
- Added `0003_gpt_bootstrap_prompt.md` as an operational bootstrap for other GPTs.
- Added `0004_session_start_protocol.md` to standardize session opening.
- Added `0005_handoff_template.md` to close a slice in a way another session can continue without assumptions.
- Updated `0001_index.md` so bootstrap and handoff files are included in the mandatory read order and module map.

## 2026-03-26 - Add Final Review Support
- Added `0006_final_review_checklist.md` for final AI_RULES package review.
- Added `scripts/audit_ai_rules.sh` as a lightweight audit helper for checking file structure and key keywords.
- Updated `0001_index.md` so the final review checklist is included in the mandatory read order and module map.
