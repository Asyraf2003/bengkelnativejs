# 021 - Refunds can be recorded on open notes

## Status

Patched, with verification gap.

## Severity

High.

## Summary

The refund endpoint allowed refunds on an open parent `Nota` when selected rows were already `close`.

Before the vulnerable change, `RecordClosedNoteRefundController` rejected refunds unless the whole note was operationally closed. The changed flow moved validation to selected rows only. `SelectedNoteRowsRefundAmountResolver` checked whether selected rows were close and capped refund amount by selected line net paid, but did not check whether the parent `Nota` itself was closed.

Because the cashier refund route is registered before the `EnsureCashierNoteAccess` group, the controller is the effective server-side state-policy enforcement point for this route.

The UI also exposed the refund form whenever any close line existed, even when the parent note still had open lines.

## Vulnerable Path

Authenticated cashier session
-> open note has one paid/close line and one open/unpaid line
-> cashier submits refund for selected close line
-> `cashier.notes.refunds.store` is reached
-> route is outside `EnsureCashierNoteAccess`
-> controller no longer checks whole-note close status
-> selected-row resolver accepts close selected row
-> refund transaction records refund/allocation
-> open parent note financial state is mutated by refund

## Root Cause

The refund policy was narrowed from whole-note closed validation to selected-row closed validation.

Selected-row validation is not enough for this business rule because the refund endpoint is named and intended as closed-note refund behavior. A line can be close while the parent note is still open.

## Patch Summary

`app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php` was changed to enforce the whole-note closed invariant before resolving or recording refunds.

The controller now:

- loads the note through `NoteReaderPort`
- rejects when the note is missing
- rejects when `NoteOperationalStatusResolver::isClose($note)` is false
- returns a refund validation error before mutation

`tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php` was updated so the open-note scenario is rejected and no refund allocation is created.

## Verification

Reported successful checks:

- `php -l app/Adapters/In/Http/Controllers/Note/RecordClosedNoteRefundController.php`
- `php -l tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`

Reported failed check:

- `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`

Failure reason:

`vendor/autoload.php` is missing in the environment.

## Verification Gap

The patch is source-level reviewed from the submitted report and patch summary, but the feature test is not proven passing in the current environment.

Future verification must run:

- `php artisan test tests/Feature/Payment/RecordSelectedRowsClosedNoteRefundHttpFeatureTest.php`

after dependencies are installed.

## Relations

Direct follow-up to #014.

#014 covers refund endpoint validation allowing open/unpaid rows to be canceled or refunded.

#021 covers a different refund-state gap: selected rows can be close, but the parent `Nota` is still open. The required invariant is whole-note closed before refund mutation.

Related to #013 because forged or invalid refund flows can produce incorrect refund/finalization behavior.

Related to #018 because refund lifecycle state affects whether notes remain mutable or terminal.

Related to #009 and #011 because mutation routes must enforce state guards before executing note/payment/refund changes.

## Related #022 - Cashier refund route bypasses note access guard

#022 is a direct follow-up on the same cashier refund endpoint. #021 covers missing whole-note close-state enforcement in the controller. #022 covers route placement outside `EnsureCashierNoteAccess`, allowing direct refund mutation against notes the cashier should not be able to access.
