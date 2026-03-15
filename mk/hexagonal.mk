.PHONY: dev fmt test test-unit test-domain test-feature test-integration test-money test-stock test-arch audit-hex migrate rollback reset-db coverage ci check

dev:
	php artisan serve

lint:
	./vendor/bin/phpstan analyze --memory-limit=-1

fmt:
	./vendor/bin/pint

test:
	php artisan test

test-unit:
	php artisan test tests/Unit

test-domain:
	php artisan test tests/Unit/Core

test-feature:
	php artisan test tests/Feature

test-integration:
	php artisan test tests/Feature

test-money:
	php artisan test tests/Unit/Core/Shared/ValueObjects/MoneyTest.php

test-stock:
	php artisan test tests/Feature/Inventory

test-arch:
	php artisan test tests/Arch

audit-hex:
	php scripts/audit-hex.php

migrate:
	php artisan migrate

rollback:
	php artisan migrate:rollback

reset-db:
	php artisan migrate:fresh

coverage:
	php artisan test --coverage

audit-lines:
	@php scripts/audit-line-count.php

audit-contract: audit-lines
	@echo "Contract audit passed."

ci: audit-hex lint audit-contract test-domain test-integration test-money test-stock

check: audit-hex test
