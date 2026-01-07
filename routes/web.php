<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

use App\Models\Product;
use App\Models\Transaction;

Route::get('/dashboard', function () {
    $productCount = Product::count();
    $recentTransactions = Transaction::where('user_id', auth()->id())->latest()->take(5)->get();

    return view('dashboard', compact('productCount', 'recentTransactions'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // User transaction history
    Route::get('/transactions', [App\Http\Controllers\UserTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}', [App\Http\Controllers\UserTransactionController::class, 'show'])->name('transactions.show');

    // Quick checkout: Buy Now endpoint (minimal MVP)
    Route::post('/checkout/buy', [App\Http\Controllers\CheckoutController::class, 'buy'])->name('checkout.buy');
});

// Public product browsing
Route::get('/products', [App\Http\Controllers\ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [App\Http\Controllers\ProductController::class, 'show'])->name('products.show');

// Public transaction viewing (admin/authorized users only)
Route::get('/transaction/view/{id}', [App\Http\Controllers\TransactionViewController::class, 'show'])->name('transaction.show');

require __DIR__.'/auth.php';

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\ProductController;
// CommentController removed (feature disabled)
use App\Http\Controllers\Admin\StockController;

Route::middleware(['auth','role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    // Settings
    Route::get('settings/tax', [\App\Http\Controllers\Admin\TaxSettingController::class, 'edit'])->name('settings.tax');
    Route::post('settings/tax', [\App\Http\Controllers\Admin\TaxSettingController::class, 'update'])->name('settings.tax.update');

    // Export route must be declared before the `transactions/{id}` resource route
    Route::get('transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::resource('transactions', TransactionController::class)->only(['index','show']);
    Route::post('transactions/{id}/status', [TransactionController::class, 'updateStatus'])->name('transactions.updateStatus');
    Route::resource('products', ProductController::class);
    // Comments admin routes removed (feature disabled)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('activity-logs/{id}', [ActivityLogController::class, 'show'])->name('activity-logs.show');

    // Stock management
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::get('products/{product}/stock', [StockController::class, 'product'])->name('products.stock');
    Route::post('products/{product}/stock', [StockController::class, 'store'])->name('products.stock.store');

    // Dev-only prototype wireframe for transactions UI/UX
    if (app()->environment('local')) {
        Route::get('transactions/prototype', function () {
            return view('admin.transactions.wireframe');
        })->name('transactions.prototype');
    }
});
