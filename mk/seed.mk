.PHONY: seed-user
seed-user:
	php artisan db:seed --class='Database\\Seeders\\CreateOnly\\CreateUserSeeder'

.PHONY: user
user: seed-user

.PHONY: seed-create-basic
seed-create-basic:
	php artisan db:seed --class='Database\\Seeders\\CreateOnly\\CreateMasterBasicSeeder'

.PHONY: product-1
product-1: seed-create-basic

.PHONY: seed-create-week
seed-create-week:
	php artisan db:seed --class='Database\\Seeders\\CreateOnly\\CreateMasterDenseWeekSeeder'

.PHONY: product-2
product-2: seed-create-week

.PHONY: seed-create-year
seed-create-year:
	php artisan db:seed --class='Database\\Seeders\\CreateOnly\\CreateMasterDenseYearSeeder'

.PHONY: product-year
product-year: seed-create-year

.PHONY: seed-create-default
seed-create-default:
	php artisan db:seed --class='Database\\Seeders\\DatabaseSeeder'
