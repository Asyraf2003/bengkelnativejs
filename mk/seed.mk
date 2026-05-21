.PHONY: seed-user
seed-user:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateUserSeeder'

.PHONY: user
user: seed-user

.PHONY: seed-create-basic
seed-create-basic:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterBasicSeeder'

.PHONY: product-1
product-1: seed-create-basic

.PHONY: seed-create-week
seed-create-week:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterDenseWeekSeeder'

.PHONY: product-2
product-2: seed-create-week

.PHONY: seed-create-year
seed-create-year:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateMasterDenseYearSeeder'

.PHONY: product-year
product-year: seed-create-year

.PHONY: seed-inventory
seed-inventory:
	php artisan db:seed --class='Database\Seeders\CreateOnly\CreateInventorySeeder'

.PHONY: inventory
inventory: seed-inventory

.PHONY: seed-create-default
seed-create-default:
	php artisan db:seed --class='Database\Seeders\DatabaseSeeder'

procurement:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateSupplierProcurementSeeder"

supplier-payment:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateSupplierPaymentSeeder"

expense:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateOperationalExpenseSeeder"

.PHONY: seed-admin-cashier-area-access
seed-admin-cashier-area-access:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateAdminCashierAreaAccessSeeder"

.PHONY: admin-cashier-area-access
admin-cashier-area-access: seed-admin-cashier-area-access

.PHONY: seed-employee-debt
seed-employee-debt:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateEmployeeDebtSeeder"

.PHONY: employee-debt
employee-debt: seed-employee-debt

.PHONY: seed-employee-debt-payment
seed-employee-debt-payment:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateEmployeeDebtPaymentSeeder"

.PHONY: employee-debt-payment
employee-debt-payment: seed-employee-debt-payment

.PHONY: seed-employee-debt-adjustment
seed-employee-debt-adjustment:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreateEmployeeDebtAdjustmentSeeder"

.PHONY: employee-debt-adjustment
employee-debt-adjustment: seed-employee-debt-adjustment

.PHONY: seed-payroll-disbursement
seed-payroll-disbursement:
	php artisan db:seed --class="Database\Seeders\CreateOnly\CreatePayrollDisbursementSeeder"

.PHONY: payroll-disbursement
payroll-disbursement: seed-payroll-disbursement

.PHONY: seed-create-all-v1
seed-create-all-v1: user admin-cashier-area-access product-1 inventory procurement supplier-payment expense employee-debt employee-debt-payment employee-debt-adjustment payroll-disbursement

.PHONY: create-all-v1
create-all-v1: seed-create-all-v1

.PHONY: seed-create-all-v2
seed-create-all-v2: user admin-cashier-area-access product-1 product-2 inventory procurement supplier-payment expense employee-debt employee-debt-payment employee-debt-adjustment payroll-disbursement

.PHONY: create-all-v2
create-all-v2: seed-create-all-v2

.PHONY: seed-create-all-v3
seed-create-all-v3: user admin-cashier-area-access product-1 product-2 product-year inventory procurement supplier-payment expense employee-debt employee-debt-payment employee-debt-adjustment payroll-disbursement

.PHONY: create-all-v3
create-all-v3: seed-create-all-v3
