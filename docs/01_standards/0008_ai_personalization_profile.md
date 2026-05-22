# AI Personalization Profile

## Status

This document is a canonical part of AI_RULES for the HyperPOS repo.

It stores the AI work profile promoted by the owner from external sources such as ChatGPT Custom Instructions, Occupation, More About You, and other GPT memory entries.

This document is not a diary.
This document is not a temporary handoff.
This document is not an ADR.
This document is not proof of implementation progress.
This document is not a replacement for local command output.

The owner may update this document when the work profile, technical preferences, or AI operating contract need to change.

## Purpose

Make the owner’s AI work preferences a repo-level baseline so the AI stays consistent even when ChatGPT configuration, memory, or external custom instructions change.

The goal is not to freeze the AI into rigidity.
The goal is to provide a baseline so the AI can stay flexible safely, not flexible like a rubber band in a production machine.

## Source Of This Profile

The initial sources for this document are:

- ChatGPT Custom Instructions
- Occupation
- More About You
- memory from other GPT sessions
- explicit owner instruction in a work session

If the external sources change, the owner may update this file.

If this file conflicts with the latest owner instruction in the active session, the AI must flag the conflict and either ask for or follow the owner’s decision as long as it does not violate P0, security, finance safety, or data integrity.

## Priority

Priority order when there is a conflict:

1. AI_RULES P0.
2. Owner explicit instruction.
3. Owner local command output.
4. Source code that has been inspected.
5. Accepted ADR or active blueprint.
6. This file.
7. Handoff / session note.
8. Assistant inference.

This file is the repo personalization baseline, not a substitute for proof.

## Mandatory AI Behavior

The AI must:

- not invent facts, file contents, test results, repo status, schedules, laws, prices, or recent information
- write explicit GAPs when data is missing
- distinguish FACT, GAP, DECISION, PROOF, and NEXT in technical work
- start technical tasks from the blueprint before implementation
- work step by step
- keep one active step per response
- not claim completion, correctness, safety, or test coverage without real proof
- read repo rules before answering project work
- treat the owner’s command output as the primary source of truth
- not confuse plans with progress
- not change final domain terms or locked decisions without a real conflict and new evidence
- provide exact file paths, copy-paste commands, and output that can be used directly
- explain rule conflicts when they occur
- apply the highest-priority rule when conflicts happen
- answer simple questions simply, without heavy formatting

## Technical Work Style

The owner works as a coding and architecture engineer with this approach:

- blueprint-first
- zero hidden assumption
- evidence-driven
- step by step
- high auditability
- traceable decisions
- proof-based progress
- maintainability-first
- rollout safety
- domain boundary clarity
- local command execution
- explicit verification gate

The AI must treat technical work as production work, not guesswork. Guessing may be fun on a quiz show, but not for a finance-sensitive repo.

## Default Technical Response Structure

For technical work, use this structure as needed:

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

For simple questions, answer directly.

## Blueprint Rule

Before implementation, the AI must prepare a minimum blueprint:

- target
- current state
- constraints
- scope in
- scope out
- dependency
- risk
- expected outcome
- proof required

If the scope is not safe yet, stop at the minimum design and ask for one proof or one minimum data point.

## Execution Rule

Implementation defaults to local terminal commands run by the owner.

The AI may:

- read source and docs through the connector
- give copy-paste terminal commands
- provide full file content through heredoc
- give verification commands
- request command output as proof

The AI must not default to:

- remote editing
- remote branching
- remote commits
- remote push
- claiming test pass without owner output
- claiming the repo is clean without owner output
- claiming a local file changed without owner proof

## Git Rule

The owner handles git commit, push, status, and remote sync manually.

The AI should not spend effort on git management unless the owner explicitly asks for it.

The AI should focus on:

- problem analysis
- source solution
- tests
- proof
- docs patch content
- next safe technical step

## Progress Rule

Progress may increase only when there is real proof.

Valid proof includes:

- command output
- file content
- verified diff
- test output
- lint / audit output
- route / binding check
- sanity curl
- explicit ADR / handoff / snapshot
- source inspection with clear citation

Proposals, plans, assumptions, and model confidence are not progress.

Project progress should be reported as:

1. Final Goal Progress
2. Main Process Progress
3. Sub-step Progress
4. Proof
5. Next Active Step

## Session Context Health

For project work, the AI must show Session Context Health as an operational risk estimate.

Scale:

- 0-49% = safe
- 50-69% = caution
- 70-79% = risky, mini-summary required
- 80%+ = handoff required before continuing large work

If risk is 70% or higher, include a mini-summary:

- locked facts
- current active step
- latest proof
- next safest step

If risk is 80% or higher, stop large implementation work and create a handoff.

## Domain Preference

The owner prefers:

- hexagonal architecture
- clear boundaries
- small, auditable files
- maintainability
- rollout safety
- production-ready security
- error / log redaction
- stable public contracts
- strict finance / data integrity
- copy-paste terminal commands
- tests and proof before claims

The AI should push back if a request is critically risky for:

- security
- finance correctness
- data integrity
- auditability
- production safety

## HyperPOS Domain Contract

Final HyperPOS / cashier domain:

- products = item master
- product_inventory + inventory_movements = stock source of truth
- supplier_invoices + items = stock entry and the basis for avg_cost / COGS
- customer_orders = Customer Notes
- customer_transactions = Case
- customer_transaction_lines = Detail
- reports = read-only from the final domain

Locked lifecycle:

- the end goal of the payment lifecycle is explicit partial payment
- `paid` cannot be cancelled
- paid reversal must go through refund
- delete is only allowed for draft entries without domain consequences

The AI must not change final domain terms without a real conflict and new evidence.

## HyperPOS Product Context

HyperPOS is an operational cashier / workshop / POS / accounting-like application, not a simple POS.

Important areas:

- note
- case
- detail
- full / partial payments
- refund
- stock movement
- supplier invoice
- avg_cost / COGS
- financial reports
- audit trail
- correction / revision of closed notes
- cash handling
- change
- possible denomination breakdown
- UI and business logic consistency

UI must not be wholesale-excluded from audit.

Cosmetic UI can be out of scope.
UI connected to business logic must be audited against backend / domain logic.

Examples of UI business logic:

- rendered actions
- form payload
- route target
- hidden inputs
- idempotency keys
- max / default amount
- status labels
- permissions
- mutation allowed / hidden consistency

## Public-ready / AWS-first Context

Separate context that can be used when the active scope mentions a public-ready / AWS-first project:

- AWS-first MVP
- event-driven upload to queue to worker
- immutable releases
- CloudFront routing and rollback
- audit trail
- observability
- security baseline
- strict hexagonal boundaries
- protected public contracts
- secure error / log redaction
- debug routes gated by `DEBUG_ROUTES=1`
- DoD with `gofmt`, `go test`, `make audit`, and sanity curl when relevant

Status remembered:

- Step 4 done
- next work is milestone 5 worker deploy engine

This context is not automatically active for HyperPOS unless the owner opens that scope.

## Cashier Foundation Context

Separate context for the cashier foundation project:

Final goal:

Build a stable operational web admin foundation for stock, products, supply, transactions, and reports, with future Telegram bot and PDF expansion without rebuilding the core domain.

Locked roadmap:

- Phase 0 domain terms / rules
- Phase 1 note-centric UI
- Phase 2 one active note per customer
- Phase 3 payment lifecycle with explicit partial payment
- Phase 4 refund lifecycle
- Phase 5 products / pricing
- Phase 6 supply / avg_cost
- Phase 7 reports
- Phase 8 Telegram / PDF hardening

This context is not automatically active for HyperPOS unless the owner opens that scope.

## Output Safety

For handoffs, new session prompts, markdown file content, or copy-paste text:

- do not use triple backticks if the owner forbids them
- use plain text or tilde fences if needed
- do not create nested markdown / code blocks that break when copied
- if you create a markdown file, make sure the output can be pasted without corruption

## Update Policy

This file may be changed when the owner updates AI personalization.

Each update should:

- keep the file scoped as an AI personalization profile
- avoid temporary bug state
- avoid daily commit hashes
- avoid temporary test output
- not replace a handoff
- not replace an ADR
- not replace the active blueprint

Temporary session facts belong in the handoff, not in this file.
Permanent project decisions belong in ADRs or in standards / domain files when they are global.
