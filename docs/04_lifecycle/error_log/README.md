# error-log

29 individual bug and security findings in the Hyperpos system.

## Index

| # | File | Topic |
|---|---|---|
| 001 | `001-refunds-counted-as-paid-in-note-totals.md` | Refunds counted as paid |
| 002 | `002-seeder-introduces-predictable-admin-credentials.md` | Seeder creates predictable admin credentials |
| 003 | `003-refunded-revised-notes-are-misclassified-as-underpaid.md` | Revised + refunded notes are misclassified as underpaid |
| 004 | `004-refunded-work-items-survive-revisions-and-inflate-stock.md` | Refunded work items survive revisions and inflate stock |
| 005 | `005-note-revision-silently-drops-overpaid-allocations.md` | Revision silently drops overpaid allocations |
| 006 | `006-client-controlled-price-basis-bypasses-minimum-price-checks.md` | Client can bypass minimum price checks via price basis |
| 007 | `007-admin-note-edit-page-exposes-stored-xss.md` | Stored XSS on the admin note edit page |
| 008 | `008-legacy-paid-notes-can-be-paid-again.md` | Old paid notes can be paid again |
| 009 | `009-cashiers-can-rewrite-closed-paid-notes-via-workspace-update.md` | Cashiers can rewrite closed notes via workspace update |
| 010 | `010-revision-reallocation-can-lose-concurrent-payments.md` | Revision reallocation can lose concurrent payments |
| 011 | `011-cashier-revision-path-mutates-settled-note-state.md` | Cashier revision path mutates settled note state |
| 012 | `012-canceled-note-rows-re-enter-payment-flows.md` | Canceled note rows re-enter payment flows |
| 013 | `013-forged-row-refund-can-auto-finalize-unpaid-notes.md` | Forged row refund can auto-finalize unpaid notes |
| 014 | `014-refund-endpoint-can-cancel-open-or-unpaid-note-rows.md` | Refund endpoint can cancel open or unpaid note rows |
| 015 | `015-refunded-notes-expose-edit-workspace.md` | Refunded notes still expose the edit workspace |
| 016 | `016-unauthenticated-admin-capability-toggle-endpoints.md` | Capability toggle endpoint is unauthenticated |
| 017 | `017-workspace-edit-payments-ignore-existing-note-payments.md` | Workspace edit ignores existing note payments |
| 018 | `018-refunded-notes-bypass-cashier-closed-note-guards.md` | Refunded notes bypass cashier closed-note guards |
| 019 | `019-cashiers-can-list-historical-closed-notes-by-date.md` | Cashiers can list historical closed notes by date |
| 020 | `020-admin-note-actions-bypass-transaction-capability.md` | Admin note actions bypass the transaction capability check |
| 021 | `021-refunds-can-be-recorded-on-open-notes.md` | Refunds can be recorded on open notes |
| 022 | `022-cashier-refund-route-bypasses-note-access-guard.md` | Cashier refund route bypasses the note access guard |
| 023 | `023-public-helper-can-expose-private-storage.md` | Public helper can expose private storage |
| 024 | `024-reflected-xss-in-expense-create-json-config.md` | Reflected XSS in the expense-create JSON config |
| 025 | `025-reflected-javascript-url-in-product-return-link.md` | Reflected JavaScript URL in the product return link |
| 026 | `026-concurrent-note-payments-can-over-allocate-balances.md` | Concurrent note payments can over-allocate balances |
| 027 | `027-admin-invoice-creation-bypasses-transaction-entry-gate.md` | Admin invoice creation bypasses the transaction-entry gate |
| 028 | `028-di-fix-exposes-unsafe-proof-attachment-content-type.md` | DI fix exposes unsafe proof attachment content type |
| 029 | `029-cashier-create-page-leaks-total-note-count.md` | Cashier create page leaks total note count |

## Rules

- File status may only be updated after there is proof.
- Allowed statuses: Reported, Planned, Patched with verification gap, Fixed with proof, Deferred with owner acceptance.
- The remediation blueprint lives in `docs/03_blueprints/error_log_remediation/`.
