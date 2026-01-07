<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register role middleware alias for route groups
        $router = $this->app->make(\Illuminate\Routing\Router::class);
        $router->aliasMiddleware('role', \App\Http\Middleware\EnsureUserHasRole::class);

        // Load API routes (this app does not have a RouteServiceProvider by default)
        \Illuminate\Support\Facades\Route::prefix('api')->middleware('api')->group(base_path('routes/api.php'));

        // Register model observers for audit logging
        \App\Models\Product::observe(\App\Observers\ProductObserver::class);
        \App\Models\Transaction::observe(\App\Observers\TransactionObserver::class);
        \App\Models\StockMovement::observe(\App\Observers\StockMovementObserver::class);
        // CommentObserver unregistered (comments feature disabled)

    }
}
