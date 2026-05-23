# Create/Edit Transaction Contract Matrix

## Status

Execution blueprint.

This document maps create/edit transaction workspace behavior before implementation hardening.

This document is not implementation proof.

This document does not change runtime behavior.

This document does not authorize refund, audit outbox expansion, seeder rewrite, or broad transaction rewrite.

## Purpose

Create/edit transaction workspace is the domain foundation for later refund maturity and audit migration.

Refund depends on stable note, work item, payment allocation, stock, and projection facts.

Audit grid expansion depends on stable mutation contracts.

This document defines the current create/edit mutation contract, known gaps, and required proof before code changes.

## Source Of Truth

Source priority follows `docs/04_lifecycle/handoff/README.md`.

Primary inspected source anchors:

- `docs/03_blueprints/db/0014_migration_readiness_dependency_grid.md`
- `docs/02_architecture/adr/0028_mysql_to_postgresql_and_api_migration_readiness.md`
- `docs/03_blueprints/db/go_postgres_migration_readiness/findings/04_transaction_idempotency_audit.md`
- `app/Application/Note/UseCases/CreateTransactionWorkspaceHandler.php`
- `app/Application/Note/UseCases/UpdateTransactionWorkspaceHandler.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/UpdateTransactionWorkspaceWorkItemPersister.php`
- `app/Application/Note/Services/CreateTransactionWorkspaceInlinePaymentRecorder.php`
- `app/Application/Note/Services/ReverseIssuedInventoryByNoteService.php`
- `app/Application/Note/Services/AutoCloseNoteWhenFullyPaid.php`

## Current Source Facts

### Create Transaction Workspace

`CreateTransactionWorkspaceHandler` currently performs this orchestration:

1. begin transaction;
2. create note from payload;
3. persist note;
4. persist work items;
5. update note total;
6. record inline payment;
7. record legacy audit `transaction_workspace_created`;
8. sync note history projection;
9. commit transaction.

Failure handling:

- `DomainException` rolls back and returns failure;
- other `Throwable` rolls back and rethrows.

### Update Transaction Workspace

`UpdateTransactionWorkspaceHandler` currently performs this orchestration:

1. begin transaction;
2. assert note is editable;
3. load note by id;
4. update note header;
5. persist header;
6. persist replacement work items;
7. update note total;
8. record inline payment;
9. record legacy audit `transaction_workspace_updated`;
10. sync note history projection;
11. commit transaction.

Failure handling:

- `DomainException` rolls back and returns failure;
- other `Throwable` rolls back and rethrows.

### Work Item Persistence

Create work item persistence:

- maps each item payload;
- builds work item;
- adds work item to note aggregate;
- persists work item;
- issues inventory for each store stock line;
- collects package allocation audit metadata.

Update work item persistence:

- reverses issued inventory for existing note work items;
- deletes work items by note id;
- asks writer for next line number;
- replaces note work items in memory;
- delegates new item persistence to create persister.

### Inline Payment Recording

Inline payment recording:

- may skip payment;
- creates customer payment;
- creates cash detail when relevant;
- checks allocation policy;
- allocates payment across payable components;
- persists payment;
- persists component allocations;
- auto-closes note when fully paid;
- records legacy audit `payment_allocated`;
- returns inline payment summary.

### Auto Close

Auto close:

- reads allocated total and refunded total;
- computes net paid;
- closes note when net paid covers note total;
- updates note operational state;
- records timeline event `note_closed`.

## Mutation Matrix

| Mutation | Boundary | Writes / Effects | Audit | Projection | Migration Risk |
|---|---|---|---|---|---|
| Create transaction workspace | TransactionManagerPort begin/commit/rollback | note create, work item create, inventory issue, optional payment create, payment component allocation, optional note close | legacy `transaction_workspace_created`, legacy `payment_allocated` when inline payment is recorded, timeline `note_closed` when auto-close happens | sync note projection | high: create defines source ids and component facts used by refund and audit |
| Update transaction workspace | TransactionManagerPort begin/commit/rollback | note header update, inventory reversal for old store-stock lines, work item delete/recreate, inventory issue for new store-stock lines, optional payment create/allocation, optional note close | legacy `transaction_workspace_updated`, legacy `payment_allocated` when inline payment is recorded, timeline `note_closed` when auto-close happens | sync note projection | high: update can rewrite item/component shape after initial transaction |
| Inline payment inside create/edit | Runs inside parent create/edit transaction | customer payment, cash detail, payment component allocations, possible note operational state change | legacy `payment_allocated`, timeline `note_closed` when eligible | parent handler syncs projection | high: payment allocation becomes refund input |
| Store stock item create/edit | Runs inside create/edit transaction | inventory issue on create, inventory reversal then issue on edit | currently not canonical audit in this matrix | parent handler syncs projection | high: refund can later reverse store stock inventory |

## Required Contract Decisions

### CED-001 - Transaction Boundary Contract

Status: open.

Current fact:

- create/edit use `TransactionManagerPort` with manual begin/commit/rollback.

Gap:

- no explicit isolation level;
- no retry/deadlock behavior;
- no nested transaction policy;
- no after-commit policy.

Required decision:

- define whether create/edit requires a stronger transaction manager contract before Go/API ownership.

### CED-002 - Idempotency Contract

Status: open.

Current fact:

- no idempotency key is visible in the inspected create/edit handlers.

Gap:

- duplicate submit behavior is not documented in this matrix;
- replay response policy is not defined;
- same-key different-payload rejection is not defined.

Required decision:

- decide whether create/edit requires idempotency before migration readiness closure.

### CED-003 - Update Replacement Semantics

Status: open.

Current fact:

- update reverses issued inventory, deletes existing work items, then recreates work items.

Gap:

- line number semantics after delete/recreate need proof;
- payment allocation behavior after item replacement needs proof;
- refund implications for already-paid or already-refunded notes need proof.

Required decision:

- define whether edit is allowed only before payment/refund, or how paid/refunded edits are constrained.

### CED-004 - Inline Payment Coupling

Status: open.

Current fact:

- create/edit can record inline payment inside the same transaction.

Gap:

- payment allocation is refund input;
- payment audit remains legacy;
- no canonical audit facts are defined for inline payment in this matrix.

Required decision:

- decide whether inline payment remains inside create/edit use case or becomes a separate mutation contract.

### CED-005 - Audit Contract

Status: open.

Current fact:

- create/edit workspace still use legacy `AuditLogPort`;
- payment allocation inside create/edit also uses legacy audit;
- audit outbox selected proof currently covers expense category only.

Gap:

- canonical `audit_events` payload for create/edit is not defined;
- before/after snapshots are not defined;
- aggregate/source id policy is not defined.

Required decision:

- block create/edit audit outbox expansion until create/edit mutation contract is proven.

### CED-006 - Projection Contract

Status: open.

Current fact:

- create/edit call `NoteHistoryProjectionService::syncNote` before commit.

Gap:

- projection table effects are not mapped in this document yet;
- report-read expectations after create/edit are not listed.

Required decision:

- define projection/report assertions needed for create/edit regression.

## Required Proof Before Implementation Patch

Before modifying create/edit logic, provide:

1. source inspection for route/controller request shape;
2. source inspection for note writer and work item writer effects;
3. source inspection for payment allocation tables touched by inline payment;
4. source inspection for projection sync output;
5. list of current create/edit tests or proof that tests are missing;
6. table write map;
7. rollback characterization;
8. duplicate submit/idempotency characterization;
9. paid-note edit policy proof;
10. refunded-note edit policy proof or explicit scope-out.

## Suggested Focused Test Matrix

| Test Area | Required Behavior |
|---|---|
| Create without inline payment | creates note/work items, updates total, no payment allocation |
| Create with inline payment | creates payment/component allocations and can auto-close note |
| Create with store stock item | issues inventory movement |
| Update unpaid note | updates header/items/total and syncs projection |
| Update store stock item | reverses previous issue and issues new inventory |
| Update with inline payment | creates additional payment allocation safely |
| Update paid note | either rejected or proven safe |
| Update refunded note | either rejected or proven safe |
| Rollback after failure | no partial note/item/payment/inventory/projection/audit writes |
| Duplicate submit | idempotency policy is proven or accepted-risk is documented |

## Work Gates

| Gate | Opens | Blocks |
|---|---|---|
| table write map exists | focused characterization tests | implementation patch |
| current tests inventory exists | gap-based test patch | guessing coverage |
| rollback characterization exists | safe transaction hardening | partial-write regressions |
| paid/refunded edit policy exists | refund matrix | refund built on unstable edit semantics |
| audit fact contract exists | audit outbox expansion | legacy/canonical churn |
| projection assertions exist | migration-readiness proof | report drift |

## Current Decision

Create/edit transaction maturity remains the next domain driver.

Do not start refund hardening until create/edit contract gaps are mapped.

Do not expand audit outbox to create/edit until create/edit mutation contract is proven.

Do not use seeder hardening to define domain semantics.

Seeder hardening may be pulled earlier only if it blocks focused proof.

## Next Active Step

Inspect create/edit route, controller, request, persistence adapters, projection sync, and existing tests.

Recommended next document or patch target:

- update this matrix with table write map and test inventory; or
- create a focused characterization test if test inventory proves a missing critical path.

No implementation patch should be made before that inspection is complete.
