<?php

use Illuminate\Support\Facades\Route;

// Auth Controllers
use App\Http\Controllers\Auth\{ShowLoginController, LoginController, LogoutController};

// Admin Controllers
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

/*
|--------------------------------------------------------------------------
| Root Route
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('admin.dashboard');
    }
    // Audit: Sudah disesuaikan ke 'login' agar sinkron dengan rute baru
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Guest Routes (Belum Login)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    // Audit: Menggunakan nama 'login' untuk mencegah RouteNotFoundException dari middleware auth
    Route::get('/login', ShowLoginController::class)->name('login');
    Route::post('/login', LoginController::class)->name('login.perform');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::post('/logout', LogoutController::class)
    ->middleware('auth')
    ->name('logout');

/*
|--------------------------------------------------------------------------
| Admin Only Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'admin.only'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        
        // Dashboard
        Route::view('/', 'admin.dashboard')->name('dashboard');

        // Products Management
        Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', ProductsIndex::class)->name('index');
            Route::get('/create', ProductsCreate::class)->name('create');
            Route::post('/', ProductsStore::class)->name('store');
            Route::get('/{product}/edit', ProductsEdit::class)->name('edit');
            Route::put('/{product}', ProductsUpdate::class)->name('update');
            Route::post('/{product}/toggle-active', ProductsToggle::class)->name('toggle');
        });

        // Inventory Adjustments
        Route::prefix('inventory/adjustments')->name('inventory.adjustments.')->group(function () {
            Route::get('/create', AdjCreate::class)->name('create');
            Route::post('/', AdjStore::class)->name('store');
        });

        // Invoices Proofs
        // Audit: Dipindahkan ke dalam grup admin agar otomatis mendapatkan prefix 'admin/' 
        // dan nama 'admin.invoices.proofs.*' secara konsisten.
        Route::prefix('invoices/{invoice}/proofs')->name('invoices.proofs.')->group(function () {
            Route::get('/', InvoiceProofIndex::class)->name('index');
            Route::post('/', InvoiceProofUpload::class)->name('upload');
            Route::get('/{media}', InvoiceProofDownload::class)->name('download');
        });
        
    });
