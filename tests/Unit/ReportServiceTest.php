<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\ReportService;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Category;
use App\Models\Product;
use Carbon\Carbon;

class ReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_per_month_counts_paid_transactions()
    {
        $year = 2025;

        // create a user for FK
        $user = \App\Models\User::factory()->create();

        // Create transactions in Jan and Mar
        Transaction::create([ 'user_id' => $user->id, 'code' => 'T1-'.uniqid(), 'subtotal' => 100, 'grand_total' => 100, 'status' => 'PAID', 'transacted_at' => Carbon::create($year, 1, 10) ]);
        Transaction::create([ 'user_id' => $user->id, 'code' => 'T2-'.uniqid(), 'subtotal' => 200, 'grand_total' => 200, 'status' => 'PAID', 'transacted_at' => Carbon::create($year, 3, 5) ]);
        Transaction::create([ 'user_id' => $user->id, 'code' => 'T3-'.uniqid(), 'subtotal' => 50,  'grand_total' => 50,  'status' => 'PAID', 'transacted_at' => Carbon::create($year, 3, 25) ]);

        // Non-paid or different year should be ignored
        Transaction::create([ 'user_id' => $user->id, 'code' => 'T4-'.uniqid(), 'subtotal' => 10,  'grand_total' => 10,  'status' => 'PENDING', 'transacted_at' => Carbon::create($year, 3, 30) ]);
        Transaction::create([ 'user_id' => $user->id, 'code' => 'T5-'.uniqid(), 'subtotal' => 20,  'grand_total' => 20,  'status' => 'PAID', 'transacted_at' => Carbon::create($year - 1, 3, 15) ]);

        $service = $this->app->make(ReportService::class);
        $result = $service->transactionsPerMonth($year);

        $this->assertCount(12, $result['labels']);
        $this->assertEquals('Jan', $result['labels'][0]);

        // values array should have 1 for Jan, 2 for Mar (index 2), and 0 elsewhere
        $this->assertEquals(1, $result['values'][0]);
        $this->assertEquals(2, $result['values'][2]);
        for ($i = 0; $i < 12; $i++) {
            if (!in_array($i, [0,2])) {
                $this->assertEquals(0, $result['values'][$i]);
            }
        }
    }

    public function test_transactions_per_month_counts_complete_transactions()
    {
        $year = 2025;
        $user = \App\Models\User::factory()->create();

        // A COMPLETE and a COMPLETED transaction in March should be counted as paid
        Transaction::create([ 'user_id' => $user->id, 'code' => 'C1-'.uniqid(), 'subtotal' => 100, 'grand_total' => 100, 'status' => 'COMPLETE', 'transacted_at' => Carbon::create($year, 3, 5) ]);
        Transaction::create([ 'user_id' => $user->id, 'code' => 'C2-'.uniqid(), 'subtotal' => 110, 'grand_total' => 110, 'status' => 'COMPLETED', 'transacted_at' => Carbon::create($year, 3, 15) ]);

        $service = $this->app->make(ReportService::class);
        $result = $service->transactionsPerMonth($year);

        // March (index 2) should be 2 (COMPLETE + COMPLETED)
        $this->assertEquals(2, $result['values'][2]);
    }

    public function test_category_share_aggregates_and_computes_percentages()
    {
        // Setup categories and products
        $catA = Category::create(['name' => 'Alpha', 'slug' => 'alpha']);
        $catB = Category::create(['name' => 'Beta', 'slug' => 'beta']);

        $p1 = Product::create(['category_id' => $catA->id, 'name' => 'P1', 'slug' => 'p1', 'purchase_price' => 10, 'selling_price' => 100, 'is_active' => true]);
        $p2 = Product::create(['category_id' => $catB->id, 'name' => 'P2', 'slug' => 'p2', 'purchase_price' => 10, 'selling_price' => 50, 'is_active' => true]);

        // create user for transactions (FK)
        $user = \App\Models\User::factory()->create();

        $start = Carbon::now()->subDays(10)->startOfDay();
        $end = Carbon::now()->endOfDay();

        // Create two paid transactions
        $t1 = Transaction::create([ 'user_id' => $user->id, 'code' => 'A1-'.uniqid(), 'subtotal' => 300, 'grand_total' => 300, 'status' => 'PAID', 'transacted_at' => Carbon::now()->subDays(5) ]);
        $t2 = Transaction::create([ 'user_id' => $user->id, 'code' => 'A2-'.uniqid(), 'subtotal' => 100, 'grand_total' => 100, 'status' => 'PAID', 'transacted_at' => Carbon::now()->subDays(2) ]);

        $start = Carbon::now()->subDays(10)->startOfDay();
        $end = Carbon::now()->endOfDay();

        // Create two paid transactions
        $t1 = Transaction::create([ 'user_id' => 1, 'code' => 'A1', 'subtotal' => 300, 'grand_total' => 300, 'status' => 'PAID', 'transacted_at' => Carbon::now()->subDays(5) ]);
        $t2 = Transaction::create([ 'user_id' => 1, 'code' => 'A2', 'subtotal' => 100, 'grand_total' => 100, 'status' => 'PAID', 'transacted_at' => Carbon::now()->subDays(2) ]);

        // Add transaction items: p1 -> 300, p2 -> 100
        TransactionItem::create(['transaction_id' => $t1->id, 'product_id' => $p1->id, 'qty' => 3, 'price' => 100, 'line_total' => 300]);
        TransactionItem::create(['transaction_id' => $t2->id, 'product_id' => $p2->id, 'qty' => 2, 'price' => 50, 'line_total' => 100]);

        $service = $this->app->make(ReportService::class);
        $res = $service->categoryShare($start->toDateTimeString(), $end->toDateTimeString());

        // Totals
        $this->assertEquals(400.0, $res['total_sum']);

        // Cache keys should have been written. Support both taggable and non-taggable stores.
        $cacheKey = "reports:category_share:{$start->toDateTimeString()}:{$end->toDateTimeString()}:10";
        try {
            $supported = true;
            \Illuminate\Support\Facades\Cache::tags(['reports','category_share'])->has($cacheKey);
        } catch (\BadMethodCallException $e) {
            $supported = false;
        }

        if ($supported) {
            $this->assertTrue(\Illuminate\Support\Facades\Cache::tags(['reports','category_share'])->has($cacheKey));
        } else {
            $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKey));
        }

        // Labels and values should reflect totals in descending order (Alpha 300 then Beta 100)
        $this->assertEquals(['Alpha','Beta'], $res['labels']);
        $this->assertEquals([300.0, 100.0], $res['values']);

        // Totals array should include percentage values 75.0 and 25.0 respectively
        $this->assertCount(2, $res['totals']);
        $this->assertEquals(75.0, $res['totals'][0]['percentage']);
        $this->assertEquals(25.0, $res['totals'][1]['percentage']);
    }
}
