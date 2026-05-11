# Handoff - Product Recreated Seeder Idempotency Slice

Date: 2026-05-11

## Final Goal

Clean seeder migration for HyperPOS.

The final goal is to make seeders deterministic, rerunnable, auditable, and safe, while keeping current `database/seeders/**/*.php` as legacy compatibility surface until a clean seeder contract is proven.

## Current Scope

Active slice: product seeder source inspection and first idempotency patch.

Current target:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`

Supporting test:

- `tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php`

## Locked Decisions

- Local command output is the source of truth.
- Do not rename seeders.
- Do not rename Makefile targets.
- Do not mass-edit legacy seeder files.
- Do not update docs without proof.
- Do not claim full clean seeder migration.
- Do not claim full ProductSeeder idempotency.
- User handles commit/push manually.

## Previous Completed Slice

`docs/error_log/002-seeder-introduces-predictable-admin-credentials.md` was closed for the minimum ADR-0023 `UserSeeder` predictable credential boundary.

Previous proven pushed HEAD before this product slice:

- branch: `main`
- HEAD: `65a72c26`
- remote: `origin/main` aligned
- commit label: `commit 1843`

## Product Seeder Inventory

Product-related seeder files inspected:

- `database/seeders/ProductSeeder.php`
- `database/seeders/Product/ProductScenarioActiveBasicSeeder.php`
- `database/seeders/Product/ProductScenarioEditedSeeder.php`
- `database/seeders/Product/ProductScenarioSoftDeletedSeeder.php`
- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`
- `database/seeders/Product/ProductScenarioLegacyIncompleteSeeder.php`
- `database/seeders/Product/ProductSeedCatalog.php`
- `database/seeders/Product/ProductSeedThresholds.php`
- `database/seeders/Load/ProductLoadSeeder.php`
- `database/seeders/ProductInventoryThresholdBackfillSeeder.php`

`ProductSeeder` calls:

- `ProductScenarioActiveBasicSeeder`
- `ProductScenarioEditedSeeder`
- `ProductScenarioSoftDeletedSeeder`
- `ProductScenarioRecreatedSeeder`
- `ProductScenarioLegacyIncompleteSeeder`

## Source Inspection Findings

### ProductScenarioActiveBasicSeeder

Status: rerun noisy / not clean idempotent.

Reason:

- It always calls `CreateProductHandler`.
- It has no pre-check for existing product code.
- On rerun, duplicate creation returns failure and logs warning.

### ProductScenarioEditedSeeder

Status: mostly idempotent.

Reason:

- It resolves existing product id by create code or update code.
- It creates only when missing.
- It updates existing product afterward.

### ProductScenarioSoftDeletedSeeder

Status: guarded idempotent-ish.

Reason:

- It checks `productCodeAlreadySeeded()` before create/delete.
- Existing product code causes the item to be skipped.

### ProductScenarioRecreatedSeeder

Status before patch: rerun noisy.

Reason:

- It always tried to create original product, soft delete it, then create replacement.
- Original and replacement use the same `PRD-RCR-*` code.
- On rerun, active replacement already exists, so original create fails with duplicate behavior.
- The seeder logged four warnings on rerun.

### ProductScenarioLegacyIncompleteSeeder

Status: legacy compatibility / high-risk by design.

Reason:

- It directly inserts into `products`.
- It intentionally simulates incomplete legacy product history.
- It should not be treated as clean final seeder contract.

### ProductLoadSeeder

Status: mostly idempotent load path.

Reason:

- It uses create-or-update behavior by product code.

### ProductInventoryThresholdBackfillSeeder

Status: targeted backfill / non-destructive looking.

Reason:

- It updates active products with null thresholds only when inventory/costing rows exist.

## Product Application Behavior Proved

`CreateProductHandler` behavior:

- Duplicate product create returns failure.
- Duplicate create is not treated as idempotent success.

`ProductDuplicateLookupQuery` behavior:

- Duplicate lookup checks only active products using `whereNull('deleted_at')`.

Product database behavior:

- Product migrations include soft-delete columns.
- Product migrations include normalized search columns.
- Product migrations include `active_unique_marker`.
- Product migrations include threshold columns.

Writer behavior:

- `ProductWritePayloads` writes normalized fields and threshold columns.

Soft delete behavior:

- `SoftDeleteProductHandler` returns failure when product is missing or already deleted.

## RED Test Added

File:

- `tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php`

Test name:

- `test_recreated_product_scenario_can_be_rerun_without_warning_or_state_growth`

RED proof:

- Syntax passed for the test file.
- Targeted test failed.
- Failure: `Log::warning` expected exactly 0 calls, but was called 4 times.
- Result: 1 failed, 63 assertions.
- Meaning: recreated scenario state did not grow, but rerun produced 4 warning logs.

## Patch Applied Locally

File changed:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php`

Patch summary:

- Added `Illuminate\Support\Facades\DB`.
- Added `$originalCode = trim($item['original']['code'])`.
- Added guard:

  - if product code already exists in `products`, skip the recreated scenario item.

- Changed original create call to use `$originalCode`.
- Added private method `productCodeAlreadySeeded(string $kodeBarang): bool`.

Expected behavior after patch:

- First run creates original, soft-deletes original, then creates active replacement.
- Second run sees the `PRD-RCR-*` code already exists and skips the item.
- Rerun should not emit warning.
- Product state should remain stable:
  - 8 total `PRD-RCR-*` rows
  - 4 active rows
  - 4 deleted historical rows
  - each code has 1 active row and 1 deleted row
  - thresholds are present

## GREEN Proof

Latest local proof:

- `php -l database/seeders/Product/ProductScenarioRecreatedSeeder.php` passed.
- `php -l tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php` passed.
- `php artisan test tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php` passed.
- Result: 1 passed, 63 assertions.

Latest diff proof:

- `database/seeders/Product/ProductScenarioRecreatedSeeder.php | 16 +++++++++++++++-`

Source diff summary:

- Added DB import.
- Added existing-code skip guard.
- Added helper method `productCodeAlreadySeeded()`.

## Current Important Gap

The latest status output showed only:

- `M database/seeders/Product/ProductScenarioRecreatedSeeder.php`

The new test file was printed by `sed`, but status did not show it as untracked or added:

- `tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php`

This must be verified before commit.

Possible explanations:

- the test file is already tracked,
- output was incomplete,
- file is ignored,
- or the file was not included in status/diff for another reason.

Do not commit until tracking is verified.

## Required Next Active Step

Verify test file tracking and run focused product blast-radius tests.

Recommended command:

~~~bash
printf '\n== TRACKING CHECK ==\n'
git status --short --untracked-files=all
git ls-files tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php
git check-ignore -v tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php || true
git diff --stat
git diff -- database/seeders/Product/ProductScenarioRecreatedSeeder.php
git diff -- tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php

printf '\n== PRODUCT SEEDER TARGETED RERUN ==\n'
php artisan test tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php

printf '\n== FOCUSED PRODUCT BLAST RADIUS ==\n'
php artisan test \
  tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php \
  tests/Feature/ProductCatalog/CreateProductFeatureTest.php \
  tests/Feature/ProductCatalog/UpdateProductFeatureTest.php \
  tests/Feature/ProductCatalog/CreateProductThresholdFeatureTest.php \
  tests/Feature/ProductCatalog/UpdateProductThresholdFeatureTest.php \
  tests/Feature/ProductCatalog/RestoreProductFeatureTest.php \
  tests/Feature/Database/V2ProductSearchNormalizationMigrationTest.php

printf '\n== FINAL STATUS AFTER FOCUSED TESTS ==\n'
git status --short --untracked-files=all
Expected Next Proof

Expected targeted proof:

ProductSeederIdempotencyFeatureTest passes.

Expected focused blast-radius proof:

Product seeder idempotency test passes.
Product create/update tests pass.
Product threshold tests pass.
Product restore test passes.
Product search normalization migration test passes.

Expected status:

database/seeders/Product/ProductScenarioRecreatedSeeder.php modified.
tests/Feature/Seeder/ProductSeederIdempotencyFeatureTest.php tracked or clearly untracked.
No unexpected files.
Do Not Claim Yet

Do not claim:

full ProductSeeder idempotency.
ProductScenarioActiveBasicSeeder fixed.
full clean seeder migration.
full make verify green.
docs closure done.
source committed/pushed.
Recommended Closure Path

After tracking is verified and focused tests pass:

Update docs/blueprint/seeder/2026-05-11-legacy-seeder-manifest.md with the ProductScenarioRecreatedSeeder source inspection and proof.
Optionally create/update a focused seeder handoff if the session continues.
User manually commits/pushes source, test, and docs.
Verify post-commit HEAD and anchors.
Progress Snapshot

Final Goal Progress: 16% for clean seeder migration.

Governance Docs Foundation Progress: 100%.

Product Source Inspection Progress: 78%.

Product Runtime Implementation Progress: 60%.

Product Docs Closure Progress: 0%.

Session Context Health: 80%, handoff required before continuing broad work.
