# 2) Master Workflow v1

This is the build order for taking the system from foundation to stability. It is organized around your domain facts, not around what is convenient for the framework.

## Step 1 - Lock the core ADRs

Goal: lock decisions that must not drift silently.

The ADRs that should be created immediately:

- ADR-001: 1 nota multi-item
- ADR-002: stok negatif dilarang default
- ADR-003: external spare parts = case cost, not inventory
- ADR-004: minimum selling price guard
- ADR-005: paid note correction with audit
- ADR-006: default costing average, pluggable FIFO
- ADR-007: admin transaction entry behind policy
- ADR-008: audit mandatory for sensitive actions
- ADR-009: reporting = read model
- ADR-010: bot integration as an adapter
- ADR-011: money locked as integers

Required output:

- all core decisions are written down
- all teams / AI / developers refer to these ADRs

## Step 2 - Build the hexagonal skeleton

Goal: establish structure first, not features.

Work:

- susun folder core/application/ports/adapters
- prepare repository, clock, id generator, and unit-of-work abstractions
- prepare the exception / error base class and the domain error base
- prepare the audit contract

Required output:

- project skeleton berdiri
- dependency direction benar
- adapters do not leak into the domain

## Step 3 - Minimal Identity & Access

Goal: establish the role, capability, transaction access policy, and policy-change audit foundation first.

Work:

- minimum user / actor access
- role admin/kasir
- admin transaction entry capability
- transaction input access decision policy
- audit when transaction capability / policy changes
- minimum operational enable / disable capability path

Required output:

- active `admin` and `cashier` roles are live as the Identity & Access foundation
- `TransactionEntryPolicy` is live as the transaction input access decision-maker
- admin cannot automatically enter transactions without an active capability
- admin transaction capability changes are recorded
- admin transaction capability enable / disable is proven end-to-end
- real operational transaction input proof is done later in the Nota Operasional / Service-Sales Case bounded-context step once the transaction entry point exists

## Step 4 - Product Catalog

Goal: make the official item master the validation source.

Work:

- create the product master
- update the product master
- use `harga_jual` as the default / minimum price
- validate supplier invoices against the product master

Required output:

- the official product master is live as the source of truth
- `harga_jual` minimum is validated
- a new product cannot be created from a supplier invoice
- ADR-0012 domain invariant is locked: the supplier flow must not create products implicitly

## Step 5 - Supplier + Inventory Receiving

Goal: activate the official stock-entry path.

Work:

- supplier
- supplier invoice
- validate supplier invoice lines against the existing product master
- purchase price
- due date
- receive inventory
- supplier payable

Required output:

- stock entry only comes from this path
- invoice lines to the product master are valid
- stock receiving creates an official inventory movement

## Step 6 - Inventory engine

Goal: get the stock engine ready for notes.

Work:

- stock balance
- inventory movement
- stock adjustment
- negative stock policy
- average costing strategy

Required output:

- stock inflow and outflow can be recomputed
- negative stock is rejected
- average costing is available

## Step 7 - Multi-item note engine

Goal: bring the business core to life.

Work:

- create note
- add work item
- add service line
- add store-stock part line
- add customer-owned part line
- add external purchase cost line
- status per work item
- total note calculation

Required output:

- one note can contain many items
- each item can have a different status
- in-store spare parts reduce stock
- customer-owned spare parts do not reduce stock
- external spare parts do not enter inventory, but they become case cost

## Step 8 - Payment & receivable engine

Goal: make flexible payments live.

Work:

- record payment
- partial payment
- payment allocation
- outstanding calculation
- full-paid detection

Required output:

- partial payment is valid
- remaining balance is exact
- paid status is accurate
- over-allocation is rejected

## Step 9 - Correction, refund, audit

Goal: keep sensitive changes safe.

Work:

- correction flow
- paid note edit guard
- alasan wajib
- before/after snapshot otomatis
- refund / adjustment flow if needed

Required output:

- paid transactions cannot be edited freely
- corrections always have a reason
- full audit is stored

## Step 10 - Employee finance

Goal: activate the HR domain.

Work:

- employee
- payroll manual
- payroll mode harian/mingguan/bulanan
- employee debt
- debt payment

Required output:

- manual salaries with valid dates and amounts
- employee debt and debt payments are recorded

## Step 11 - Operational expense

Goal: activate official business expense tracking.

Work:

- expense category
- expense entry
- recurring template opsional

Required output:

- electricity, water, meals, and similar expenses can be recorded
- reports are affected

## Step 12 - Reporting read models

Goal: prepare critical reports.

Work:

- laporan bulanan
- arus kas
- hutang supplier
- hutang karyawan
- pendapatan nota
- biaya operasional
- stok
- laba model operasional

Required output:

- reports read from final data
- numbers are consistent
- a 1 rupiah difference is detected as a defect

## Step 13 - Notification integration

Goal: prepare the Telegram path without damaging the core.

Work:

- outbound notification adapter
- event note paid
- event supplier due soon
- event correction happened
- event daily / monthly summary

Required output:

- the domain does not know Telegram
- notifications are only an adapter

## Step 14 - Hardening & migration safety

Goal: make the project maintainable and safe to migrate.

Work:

- lock public contracts
- hexagonal audit script
- concurrency test
- data migration discipline
- replay test for reports

Required output:

- portable structure
- core is not tied to the framework
- important behavior is protected by tests
