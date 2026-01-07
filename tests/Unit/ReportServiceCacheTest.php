<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ReportService;
use App\Models\Transaction;
use Carbon\Carbon;

class ReportServiceCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_per_month_is_cached()
    {
        $year = now()->year;
        $user = \App\Models\User::factory()->create();

        Transaction::create([ 'user_id' => $user->id, 'code' => 'C1-'.uniqid(), 'subtotal' => 100, 'grand_total' => 100, 'status' => 'PAID', 'transacted_at' => Carbon::create($year, 2, 14) ]);

        $service = $this->app->make(ReportService::class);
        $res = $service->transactionsPerMonth($year);

        $cacheKey = "reports:transactions_per_month:{$year}";

        // Tests should work whether the cache supports tags or not.
        try {
            $supported = true;
            \Illuminate\Support\Facades\Cache::tags(['reports','transactions_per_month'])->has($cacheKey);
        } catch (\BadMethodCallException $e) {
            $supported = false;
        }

        if ($supported) {
            $this->assertTrue(\Illuminate\Support\Facades\Cache::tags(['reports','transactions_per_month'])->has($cacheKey));
            $this->assertEquals($res, \Illuminate\Support\Facades\Cache::tags(['reports','transactions_per_month'])->get($cacheKey));
        } else {
            $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKey));
            $this->assertEquals($res, \Illuminate\Support\Facades\Cache::get($cacheKey));
        }
    }
}
