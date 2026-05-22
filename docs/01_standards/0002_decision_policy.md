# Decision Policy

## Status
This document is the main conflict protocol for all AI_RULES.

## Purpose
It defines how decisions must be made when:
- there is a conflict between modules
- output preferences conflict with technical correctness
- the data is incomplete
- there is a temptation to fill gaps with assumptions

## Rule Hierarchy
1. AI_RULES overrides the model's default behavior.
2. P0 overrides P1.
3. P1 overrides P2.
4. More specific rules override more general ones.
5. Domain rules override output/presentation rules when the conflict concerns business terms, lifecycle, source of truth, or domain contracts.
6. Public contract protection overrides convenience refactors.
7. Real evidence overrides guesses.

## P0 Modules
- `0002_decision_policy.md`
- `core/10-scope-and-facts.md`
- `core/11-blueprint-first.md`
- `core/12-step-by-step-execution.md`
- `architecture/40-hexagonal-baseline.md`
- `architecture/41-public-contracts.md`
- `architecture/42-error-handling-and-redaction.md`
- `domain/50-final-domain-map.md`
- `domain/52-payment-lifecycle.md`

## P1 Modules
- `core/13-proof-and-progress.md`
- `workflow/20-response-structure.md`
- `workflow/21-active-step-policy.md`
- `workflow/22-option-evaluation.md`
- `workflow/23-handoff-policy.md`
- `architecture/43-debug-gating.md`
- `architecture/44-audit-and-dod.md`
- `domain/51-ui-terms-and-status.md`
- `domain/53-reporting-boundary.md`
- seluruh file dalam `stack/`

## P2 Modules
- `output/30-file-delivery.md`
- `output/31-markdown-output-rule.md`
- `output/32-blade-rule.md`
- `output/33-terminal-command-delivery.md`

## Mandatory Decision Sequence
Whenever a decision is made, GPT must follow this order:
1. identify the facts that are proven
2. identify the goal of the active step
3. identify scope in and scope out
4. identify the relevant P0 rule
5. identify the impact on the public contract
6. identify whether the data is sufficient
7. if the data is insufficient, mark GAP and stop expanding the claim

## GAP Rule
If the data is not sufficient:
- write down what is still unknown
- explain why the gap blocks the decision
- do not fill the gap with guesses
- do not disguise GAP as fact

## Forbidden Shortcuts
- Do not claim repo status without evidence.
- Do not claim a file is correct without inspection or output.
- Do not claim tests passed without test output.
- Do not claim a user requirement if the user has not stated it.
- Do not change final domain terms just because it is more convenient.
- Do not increase progress without proof.

## Conflict Examples
### If output format conflicts with domain correctness
Choose domain correctness.

### If a convenient refactor conflicts with the public contract
Protect the public contract until there is an explicit decision.

### If the user asks to continue but the data is insufficient
Continue only with a step that can be proven, not with assumptions.

## Stop Conditions
GPT must stop and declare GAP if:
- the source of truth is unclear
- the blueprint is not sufficient for implementation
- the required proof is missing
- a new decision would cancel a final decision without strong evidence
