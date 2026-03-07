<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\{
    ShowLoginController,
    LoginController,
    LogoutController
};
use App\Http\Controllers\Admin\Products\{
    IndexController as ProductsIndex,
    CreateController as ProductsCreate,
    StoreController as ProductsStore,
    EditController as ProductsEdit,
    UpdateController as ProductsUpdate,
    ToggleActiveController as ProductsToggle
};
use App\Http\Controllers\Admin\Inventory\Adjustments\{
    CreateController as AdjCreate,
    StoreController as AdjStore
};
use App\Http\Controllers\Admin\Invoices\Proofs\{
    IndexController as InvoiceProofIndex,
    UploadController as InvoiceProofUpload,
    DownloadController as InvoiceProofDownload
};
use App\Http\Controllers\Admin\OperationalExpenses\{
    IndexController as OpIndex,
    CreateController as OpCreate,
    StoreController as OpStore,
    EditController as OpEdit,
    UpdateController as OpUpdate,
    DeleteController as OpDelete
};
use App\Http\Controllers\Admin\Employees\{
    IndexController as EmpIndex,
    CreateController as EmpCreate,
    StoreController as EmpStore,
    EditController as EmpEdit,
    UpdateController as EmpUpdate,
    DeleteController as EmpDelete
};
use App\Http\Controllers\Admin\Salaries\{
    IndexController as SalIndex,
    CreateController as SalCreate,
    StoreController as SalStore,
    EditController as SalEdit,
    UpdateController as SalUpdate,
    DeleteController as SalDelete
};
use App\Http\Controllers\Admin\EmployeeLoans\{
    IndexController as LoanIndex,
    CreateController as LoanCreate,
    StoreController as LoanStore,
    EditController as LoanEdit,
    UpdateController as LoanUpdate,
    DeleteController as LoanDelete
};
use App\Http\Controllers\Admin\EmployeeLoanPayments\{
    IndexController as PayIndex,
    CreateController as PayCreate,
    StoreController as PayStore,
    EditController as PayEdit,
    UpdateController as PayUpdate,
    DeleteController as PayDelete
};
use App\Http\Controllers\Admin\Reports\{
    DailyProfitController,
    MonthlyProfitController,
    StockController,
    InvoiceDueSoonController
};
use App\Http\Controllers\Admin\Transactions\{
    IndexController as TxIndex,
    CreateController as TxCreate,
    StoreController as TxStore,
    MarkPaidController as TxMarkPaid,
    CancelController as TxCancel,
    RefundController as TxRefund
};
use App\Http\Controllers\Admin\Invoices\{
    IndexController as InvoiceIndex,
    CreateController as InvoiceCreate,
    StoreController as InvoiceStore
};

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', ShowLoginController::class)->name('login');
    Route::post('/login', LoginController::class)->name('login.perform');
});

Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'admin.only'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::view('/', 'admin.dashboard')->name('dashboard');

        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', ProductsIndex::class)->name('index');
            Route::get('/create', ProductsCreate::class)->name('create');
            Route::post('/', ProductsStore::class)->name('store');
            Route::get('/{product}/edit', ProductsEdit::class)->name('edit');
            Route::put('/{product}', ProductsUpdate::class)->name('update');
            Route::post('/{product}/toggle-active', ProductsToggle::class)->name('toggle');
        });

        Route::prefix('inventory/adjustments')->name('inventory.adjustments.')->group(function () {
            Route::get('/create', AdjCreate::class)->name('create');
            Route::post('/', AdjStore::class)->name('store');
        });

        Route::prefix('invoices/{invoice}/proofs')->name('invoices.proofs.')->group(function () {
            Route::get('/', InvoiceProofIndex::class)->name('index');
            Route::post('/', InvoiceProofUpload::class)->name('upload');
            Route::get('/{media}', InvoiceProofDownload::class)->name('download');
        });

        Route::prefix('operational-expenses')->name('operational_expenses.')->group(function () {
            Route::get('/', OpIndex::class)->name('index');
            Route::get('/create', OpCreate::class)->name('create');
            Route::post('/', OpStore::class)->name('store');
            Route::get('/{expense}/edit', OpEdit::class)->name('edit');
            Route::put('/{expense}', OpUpdate::class)->name('update');
            Route::post('/{expense}/delete', OpDelete::class)->name('delete');
        });

        Route::prefix('employees')->name('employees.')->group(function () {
            Route::get('/', EmpIndex::class)->name('index');
            Route::get('/create', EmpCreate::class)->name('create');
            Route::post('/', EmpStore::class)->name('store');
            Route::get('/{employee}/edit', EmpEdit::class)->name('edit');
            Route::put('/{employee}', EmpUpdate::class)->name('update');
            Route::post('/{employee}/delete', EmpDelete::class)->name('delete');
        });

        Route::prefix('salaries')->name('salaries.')->group(function () {
            Route::get('/', SalIndex::class)->name('index');
            Route::get('/create', SalCreate::class)->name('create');
            Route::post('/', SalStore::class)->name('store');
            Route::get('/{salary}/edit', SalEdit::class)->name('edit');
            Route::put('/{salary}', SalUpdate::class)->name('update');
            Route::post('/{salary}/delete', SalDelete::class)->name('delete');
        });

        Route::prefix('employee-loans')->name('employee_loans.')->group(function () {
            Route::get('/', LoanIndex::class)->name('index');
            Route::get('/create', LoanCreate::class)->name('create');
            Route::post('/', LoanStore::class)->name('store');
            Route::get('/{loan}/edit', LoanEdit::class)->name('edit');
            Route::put('/{loan}', LoanUpdate::class)->name('update');
            Route::post('/{loan}/delete', LoanDelete::class)->name('delete');

            Route::prefix('{loan}/payments')->name('payments.')->group(function () {
                Route::get('/', PayIndex::class)->name('index');
                Route::get('/create', PayCreate::class)->name('create');
                Route::post('/', PayStore::class)->name('store');
                Route::get('/{payment}/edit', PayEdit::class)->name('edit');
                Route::put('/{payment}', PayUpdate::class)->name('update');
                Route::post('/{payment}/delete', PayDelete::class)->name('delete');
            });
        });

        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/daily-profit', DailyProfitController::class)->name('daily_profit');
            Route::get('/monthly-profit', MonthlyProfitController::class)->name('monthly_profit');
            Route::get('/stock', StockController::class)->name('stock');
            Route::get('/invoice-due-soon', InvoiceDueSoonController::class)->name('invoice_due_soon');
        });

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', TxIndex::class)->name('index');
            Route::get('/create', TxCreate::class)->name('create');
            Route::post('/', TxStore::class)->name('store');
            Route::post('/{transaction}/mark-paid', TxMarkPaid::class)->name('mark_paid');
            Route::post('/{transaction}/cancel', TxCancel::class)->name('cancel');
            Route::get('/{transaction}/refund', TxRefund::class)->name('refund');
            Route::post('/{transaction}/refund', TxRefund::class)->name('refund.store');
        });

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', InvoiceIndex::class)->name('index');
            Route::get('/create', InvoiceCreate::class)->name('create');
            Route::post('/', InvoiceStore::class)->name('store');

            Route::prefix('{invoice}/proofs')->name('proofs.')->group(function () {
                Route::get('/', InvoiceProofIndex::class)->name('index');
                Route::post('/', InvoiceProofUpload::class)->name('upload');
                Route::get('/{media}', InvoiceProofDownload::class)->name('download');
            });
        });
    });
