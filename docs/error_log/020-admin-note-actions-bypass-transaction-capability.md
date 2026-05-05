# 020 - Admin note actions bypass transaction capability

## Status

Patched.

## Severity

High.

## Summary

Admin note mutation routes allowed financial and workspace mutations without enforcing the transaction-entry capability gate.

The affected admin routes were:

- `admin.notes.refunds.store`
- `admin.notes.payments.store`
- `admin.notes.rows.store`
- `admin.notes.workspace.update`

The admin route group was protected by authentication, admin-page access, and app shell middleware, but did not require `EnsureTransactionEntryAllowed`.

Because admin note detail UI supplied admin payment/refund action URLs and rendered payment/refund forms, an authenticated admin whose transaction-entry capability was inactive could still submit normal admin note mutation forms and change financial records.

## Vulnerable Path

Authenticated admin session
-> admin role passes `EnsureAdminPageAccess`
-> open `/admin/notes/{noteId}`
-> page renders payment/refund/add-row/workspace actions
-> submit normal form with valid session/CSRF
-> admin mutation route lacks `EnsureTransactionEntryAllowed`
-> payment/refund/row/workspace controller executes
-> note financial or workspace state changes without required transaction-entry capability

## Root Cause

The transaction-entry capability boundary existed but was not applied to admin note mutation routes.

The admin page-access gate only proves the actor can access admin pages. It does not prove the actor is allowed to input or mutate transactions.

## Patch Summary

`routes/web/note.php` was changed so these admin mutation routes are wrapped in `EnsureTransactionEntryAllowed`:

- `Route::post('/{noteId}/refunds', RecordClosedNoteRefundController::class)->name('refunds.store');`
- `Route::post('/{noteId}/payments', RecordNotePaymentController::class)->name('payments.store');`
- `Route::post('/{noteId}/rows', AddNoteRowsController::class)->name('rows.store');`
- `Route::patch('/{noteId}/workspace', StoreNoteRevisionController::class)->name('workspace.update');`

Admin read routes remain outside the transaction-entry gate.

## Verification

Reported proof:

- `php -l routes/web/note.php`
- `git status --short`
- `git commit -m "Protect admin note mutations with transaction entry gate"`

## Verification Gap

No framework feature test result was included.

Recommended future proof:

- `php artisan route:list --path=admin/notes`
- feature test proving an admin with inactive transaction-entry capability receives forbidden/redirect behavior on:
  - payment route
  - refund route
  - add rows route
  - workspace update route
- feature test proving admin read routes still work without transaction-entry capability.

## Relations

Directly related to #016 as part of the identity/access capability authorization cluster.

#016 covers unauthenticated admin capability toggle endpoints.

#020 covers admin note mutation routes bypassing the transaction-entry capability gate.

Related to #009, #011, and #017 because those reports also involve note mutation paths where authorization and settlement safety must be enforced before executing payment/revision/workspace changes.

## Update - Duplicate report from commit a78999d

This report is classified as an update to #020, not a new error-log file.

The root cause is identical: four state-changing admin note routes were exposed under `admin/notes` without `EnsureTransactionEntryAllowed` / `transaction.entry`.

Affected routes:

- `admin.notes.refunds.store`
- `admin.notes.payments.store`
- `admin.notes.rows.store`
- `admin.notes.workspace.update`

Additional evidence in this report confirms the impact through the reached controllers/use cases:

- `RecordNotePaymentController` records and allocates note payments once reached.
- `RecordClosedNoteRefundController` records customer refunds once reached.
- `AddNoteRowsController` mutates note rows once reached.
- `UpdateTransactionWorkspaceHandler` changes note header, items, and totals once reached.

Additional policy evidence:

- `AdminPageAccessPolicy` only checks whether the actor is an admin.
- `TransactionEntryPolicy` separately denies inactive admin transaction capability.
- Because the vulnerable admin routes skipped the transaction-entry gate, that denial policy was not reached.

Patch variant:

The reported fix wraps only the four admin mutation routes in:

`Route::middleware(EnsureTransactionEntryAllowed::class)->group(...)`

Admin read-only routes remain outside that middleware group.

Reported verification:

- `php -l routes/web/note.php`
- `git status --short`
- `git commit -m "Protect admin note mutation routes with transaction-entry middleware"`

No progress increase because this is the same root cause and same target file as #020.

## Related #027 - Admin invoice creation bypasses transaction-entry gate

#027 is related through the same admin transaction-entry capability boundary. #020 covers admin note mutation routes missing `EnsureTransactionEntryAllowed`, while #027 covers admin procurement supplier-invoice creation missing `transaction.entry`.
