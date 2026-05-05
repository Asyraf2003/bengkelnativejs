# 029 - Cashier create page leaks total note count

Status: reported
Severity: Medium
Classification: new unique error-log file
Introduced commit: 69cf998
Patch status: not provided in this report

## Summary

The cashier create transaction workspace page leaks the global total note count through the generated default customer name.

`CreateTransactionWorkspacePageController` calls `CreateTransactionWorkspacePageDataBuilder::build()` and reads `defaultCustomerName`. The builder derives that value as `Pelanggan no ` plus `NoteReaderPort::countAll() + 1`.

The production note reader adapter implements `countAll()` as an unrestricted count over the entire `notes` table. That value is then rendered to the cashier-visible create workspace page as the default customer name or placeholder, and is also available through page configuration data.

Cashier note browsing is otherwise date-windowed, so exposing the unrestricted global note count crosses the intended cashier visibility boundary and leaks business-volume metadata.

## Why this is new

This is not the same issue as the historical closed note disclosure report. That issue exposes historical note rows through cashier-accessible browsing behavior.

This issue exposes aggregate global note volume through the cashier create page default customer label. The leaked value is not a note row, but it still reveals business-volume metadata outside the cashier's normal date-windowed visibility.

## Affected files

- `app/Adapters/In/Http/Controllers/Cashier/Note/CreateTransactionWorkspacePageController.php`
- `app/Application/Note/Services/CreateTransactionWorkspacePageDataBuilder.php`
- `app/Adapters/Out/Note/DatabaseNoteReaderAdapter.php`
- `app/Adapters/Out/Note/Queries/CashierNoteHistoryBaseQuery.php`
- `resources/views/cashier/notes/workspace/partials/info-card.blade.php`

## Evidence

`CreateTransactionWorkspacePageController` calls the page data builder and uses `defaultCustomerName` when there is no old input or draft customer name override.

`CreateTransactionWorkspacePageDataBuilder::build()` creates:

- `defaultCustomerName` = `Pelanggan no ` plus `NoteReaderPort::countAll() + 1`

`DatabaseNoteReaderAdapter::countAll()` performs an unrestricted `notes` table count.

`CashierNoteHistoryBaseQuery` restricts cashier history visibility to a selected date window, showing that unrestricted lifetime note count is broader than ordinary cashier note visibility.

The Blade workspace info card renders the derived default customer name into the cashier-visible create page.

## Attack path

Authenticated cashier session -> open create transaction workspace -> controller calls page data builder -> builder calls unrestricted note count -> database adapter counts all notes -> page renders `Pelanggan no {global_count + 1}` -> cashier infers global lifetime note count or business volume metadata.

## Impact

A cashier can infer the global number of notes or transactions, including records outside the normal cashier date window.

The impact is medium because this leaks aggregate business-volume metadata but does not expose full note contents, customer PII, credentials, payment details, inventory data, or write capability.

## Preconditions

- The Laravel web application serves the cashier note workspace route.
- The actor has an authenticated cashier-capable session.
- The actor can access the create transaction workspace.
- No old input or draft customer name overrides the generated default value.
- Global note count is considered business-sensitive metadata not intended for cashier-wide visibility.

## Controls present

- Route requires Laravel session authentication.
- Cashier area access middleware applies.
- Transaction entry middleware applies.
- Cashier history queries are date-windowed.
- Blade output escaping reduces script injection risk but does not prevent metadata disclosure.

## Controls missing

- `countAll()` is unrestricted and not scoped to cashier visibility.
- Default customer naming depends on global note table volume.
- The create page exposes a global aggregate that is broader than cashier history scope.
- No separate non-sensitive sequence source is used for cashier-facing placeholder labels.

## Recommended fix

Do not derive cashier-facing default customer names from unrestricted global note count.

Use one of these safer approaches:

1. Use a neutral placeholder such as `Pelanggan baru`.
2. Use a per-session or per-draft temporary label that is not tied to persistent global count.
3. Use a cashier-scoped/day-scoped counter if operationally required.
4. Generate the final note number only after creation, and show it only to actors allowed to view that note.

If a count is required, expose only a scoped count aligned with the cashier's authorized visibility window.

## Verification gap

This session has not independently verified the local repository diff or runtime behavior. Treat this entry as report-derived until `git status --short`, `git diff`, and relevant test output are provided.

The report states that full Laravel HTTP execution was not performed because dependencies/vendor were unavailable. That means runtime HTTP coverage is not proven in this session.

