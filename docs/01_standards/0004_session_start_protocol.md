# Session Start Protocol

## Purpose

This document standardizes how GPT starts a new work session so it does not jump into assumptions, wrong priorities, or implementation outside the user scope.

A new session must be able to continue the project safely from:
- the latest user prompt
- the latest handoff
- AI_RULES
- the blueprint / ADR / docs named by the user
- the user's command output
- the proven current repo state

## Mandatory Opening Flow

At the start of a technical session, GPT must:

1. Read the AI_RULES entry point and the decision policy.
2. Identify the user's active scope from the latest prompt.
3. Identify the documents the user asked to read.
4. Distinguish active-scope documents from constraint documents.
5. Identify the facts that are available.
6. Identify the user's goal.
7. Identify scope in and scope out.
8. Map the binding P0/P1 rules.
9. Read the relevant blueprint/ADR/handoff before giving guidance.
10. Build a short blueprint only for the active scope.
11. State one active step.
12. Mention the proof that exists or the minimum proof needed.
13. End with progress and session context health if this is project work.

## Active Scope Derivation

The user prompt is the primary source for active scope after AI_RULES.

GPT must follow this derivation order:

1. Explicit active step from the user.
2. Blueprint / ADR / error log named by the user.
3. The latest handoff the user provided or that exists in context.
4. The proven repo/source state.
5. Priority recommendations from the blueprint.

GPT may not choose another cluster just because it is:
- easier
- smaller
- more isolated
- high severity
- present in a global audit matrix
- more convenient for the model

If the user asks for finance residual, do not start from seeder.
If the user asks for access boundary, do not start from XSS.
If the user asks for a security blueprint as a boundary, do not automatically make it the implementation scope.
If there is a strong reason to change priority, ask for an owner decision first.

## Document Role Classification

After reading documents, GPT must classify each document's role:

- ACTIVE: the document that determines the current implementation step.
- CONSTRAINT: the document that limits the patch so it does not violate another decision.
- REFERENCE: supporting context.
- DEFERRED: valid material that is not part of the active step.

Example:

If the user asks for:
- finance residual blueprint
- blueprint/security/
- ADR-0022
- ADR-0023
- current projection ADR
- carry-forward ADR

The default classification is:

- ACTIVE: finance residual blueprint.
- CONSTRAINT: ADR-0022 if payment/concurrency is touched.
- CONSTRAINT: current projection ADR and carry-forward ADR for settlement/revision behavior.
- REFERENCE/DEFERRED: seeder credential ADR unless the user opens error log 002.
- REFERENCE/DEFERRED: public surface/security docs unless the active slice touches output/storage/URL.

## Implementation Boundary

For this project, implementation defaults to the user’s local machine.

Allowed by default:
- read the repo via connector for source/docs/commits
- provide copy-paste terminal commands
- provide full file content via heredoc
- ask for test/audit/diff output as proof

Forbidden by default:
- create a remote branch
- edit files through the remote connector
- commit through the remote connector
- push through the remote connector
- claim tests passed without user output
- claim the local working tree is clean without user output

Remote write is only allowed if the user explicitly asks for it.

## If Context Is Not Sufficient

If the context is not sufficient:

- Mark GAP explicitly.
- Do not pretend the context is enough.
- Do not write speculative implementation.
- Ask for one minimum proof, not a huge dump.
- Minimum proof may be `git status -sb`, `git rev-parse --short HEAD`, a targeted grep, or targeted test output.

## If the User Asks to Continue

If the user asks to continue:

- continue only to the next step that is valid under the workflow
- do not open two active steps at once
- do not skip validation gates
- do not switch clusters without an owner decision
- do not update `docs/04_lifecycle/error_log` before patch and test proof

## Source Of Truth Order

When there is a conflict, use this order:

1. AI_RULES P0.
2. Explicit user instruction / owner decision.
3. User command output.
4. Current source code inspected from repo/local proof.
5. Accepted ADR / active blueprint.
6. Latest handoff.
7. Doc / error-log status.
8. Assistant recommendation.

The narrative status in `docs/04_lifecycle/error_log/*.md` may not override source code or command output.

## Wrong-Scope Recovery

If GPT chooses a step outside the active scope:

1. Stop immediately.
2. State that the active step is out of scope.
3. State the correct scope.
4. Do not continue with the wrong patch.
5. If you already gave a wrong command and the user has not run it, ask them to ignore it.
6. If the local file was already created from the wrong command, provide a cleanup command.
7. Reset active implementation progress to the value before the mistake.
8. Continue only with commands that match the correct scope.

## Session Capacity Baseline

At the start of a new technical work session, GPT must initialize an operational capacity estimate.

A new page does not mean perfect 100% capability. Use the latest handoff, active repo facts, and the task complexity to estimate:

~~~text
Session capacity:
- Reasoning capacity: xx%
- Context window: xx%
- Remaining capability: xx%
- Status: safe / getting risky / new page required

For a clean new page with a reliable handoff, the usual starting range is:

Session capacity:
- Reasoning capacity: 92-95%
- Context window: 95-98%
- Remaining capability: 92-95%
- Status: safe

These are operational risk estimates, not exact internal telemetry.

Minimal Session Reminder

GPT must remember:

user prompt defines active scope
blueprint first
one active step
proof-based progress
no assumption
remote read is allowed
local command implementation is the default
no remote write unless explicitly requested
