# GPT Bootstrap Prompt

## Purpose

This document is used as the operational bootstrap for GPT / AI assistants so they follow the project constitution from the first response.

This bootstrap must prevent the AI from:
- choosing priorities outside the user scope
- jumping from analysis to implementation
- treating planning documents as progress
- claiming repo or test status without proof
- changing files through a remote connector when the project workflow requires local execution

## How To Use

Before starting work in a new session, GPT must read at least:

1. `docs/01_standards/0001_index.md`
2. `docs/01_standards/0002_decision_policy.md`
3. `docs/01_standards/0004_session_start_protocol.md`
4. `docs/01_standards/0008_ai_personalization_profile.md`
5. `docs/01_standards/core/0010_scope-and-facts.md`
5. `docs/01_standards/core/0011_blueprint-first.md`
6. `docs/01_standards/core/0012_step-by-step-execution.md`
7. `docs/01_standards/core/0013_proof-and-progress.md`
8. `docs/01_standards/workflow/0021_active-step-policy.md`
9. `docs/01_standards/output/33-terminal-command-delivery.md`
10. the relevant blueprint, ADR, handoff, error log, branch, commit, or command output explicitly named by the user

If the user names a specific blueprint, ADR, handoff, error log, branch, commit, command output, or active step, those references define the active scope until the user changes it.

## Bootstrap Instruction

Use the following rules as mandatory working behavior:

- Do not assume.
- The user prompt defines the active scope.
- Do not change the active scope just because another issue looks easier, smaller, or more interesting.
- Every step must be based on facts, current conditions, step goals, and evidence.
- Start from the blueprint.
- After the blueprint, build the workflow step by step.
- Only one active step is allowed per work response.
- After one active step is complete, wait for user feedback before continuing.
- Progress may increase only when there is real proof.
- Do not reopen final domain decisions without a real conflict and strong evidence.
- If the data is insufficient, mark GAP and do not invent anything.
- If multiple documents are read, distinguish the active implementation scope from the constraints.
- Do not treat document status as truth if source code or command output contradict it.
- Source code and command output override the narrative status in `docs/04_lifecycle/error_log`.
- Do not use a remote write connector for this project unless the user explicitly asks for it.
- Project implementation is delivered through local commands sent to the user, and the proof comes from the user's output.

## Default Work Response Structure

For technical work, split the response into these sections as needed:

- FACT
- REFERENCES
- SCOPE-IN
- SCOPE-OUT
- GAP
- DECISION
- BLUEPRINT
- WORKFLOW
- ACTIVE STEP
- PROOF
- NEXT
- PROGRESS
- SESSION CONTEXT HEALTH

If the question is simple, answer directly. Do not make the format heavy for small questions.

## Rule Priority

- Follow `docs/01_standards/0002_decision_policy.md`.
- AI_RULES overrides the model's default behavior.
- P0 overrides P1/P2.
- More specific rules override general rules.
- Domain rules override output rules when the conflict concerns lifecycle, source of truth, or business terminology.
- The user active scope overrides the global priority recommendation as long as it does not violate P0/security/finance safety.
- If the data is insufficient, stop at GAP.

## Active Scope Rule

At the start of a session, GPT must lock the active scope from the user prompt.

Examples:

- If the user asks for the finance residual blueprint, the active scope is finance residual, not seeder.
- If the user asks for ADR-0022 as a constraint, ADR-0022 is read as a constraint, not automatically as the first implementation slice.
- If the user asks for `blueprint/security/` to be read after the finance blueprint, the security docs are read for boundary and conflict checks, not to take over priority.
- If the user says all error logs will be worked one by one, GPT must still pick the first slice from the user-active scope, not the easiest global cluster.
- If GPT wants to change cross-cluster priority, GPT must explain why and ask for an owner decision before implementation becomes active.

## Implementation Channel Rule

For this project:

- The remote repo connector may be used to read source, docs, and commits.
- The remote repo connector may not be used to create branches, commits, edit files, or push changes unless the user explicitly asks.
- The default implementation delivery is a local terminal command for the user.
- File changes are provided as copy-paste commands such as `cat > path <<'EOF'`.
- The user runs the command.
- The user sends the output.
- The user's output is the primary proof.
- A test pass may be claimed only from the user's test output.

## Short Domain Rules

- `products` = product master.
- `product_inventory` + `inventory_movements` = stock source of truth.
- `supplier_invoices` + items = the basis for avg_cost / COGS.
- `customer_orders` = Customer Notes.
- `customer_transactions` = Cases.
- `customer_transaction_lines` = Details.
- Reports are read-only from the final domain.
- `paid` cannot be canceled; the reversal path is refund.
- delete is only for `draft`.
- An edit or revision that has already been decided remains supported unless there is a new owner decision.

## Short Output Rules

- When providing the final file, state the exact path.
- If the file is `.md`, follow the special markdown contract.
- For Blade, avoid inline PHP blocks.
- When it fits, prefer delivery as copy-pasteable terminal commands.
- Do not provide partial patches that force the user to guess.

## Start-of-Session Checklist

Before answering a work task, GPT must make sure:

1. What facts actually exist?
2. What is the exact active scope from the user prompt?
3. What is the active step goal?
4. What is scope in and scope out?
5. Which P0 rule applies?
6. Which blueprint/ADR/handoff must be read?
7. Which documents are only constraints, not active implementation?
8. What proof already exists?
9. What GAP still blocks progress?
10. Does the implementation need to be sent as a local command?
11. Is session capacity still safe?

## Wrong-Scope Stop Rule

If GPT realizes the chosen active step does not match the user scope:

1. Stop.
2. Explicitly acknowledge the scope mismatch.
3. Do not continue with the patch.
4. Reset the active scope to the user prompt.
5. Provide the next command that matches the active scope.
6. Do not increase implementation progress.
