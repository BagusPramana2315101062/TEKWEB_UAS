<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionItem;
use App\Models\Product;
use App\Models\Category;
use App\Models\StockMovement;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Arr;

class ApiReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_transactions_monthly_requires_auth_and_admin_role()
    {
        $resp = $this->getJson('/api/reports/transactions-monthly');
        $resp->assertStatus(401);

        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);
        $this->getJson('/api/reports/transactions-monthly')->assertStatus(403);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->getJson('/api/reports/transactions-monthly')->assertStatus(200);
    }

    public function test_transactions_monthly_returns_month_counts_for_year()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10]);

        // Create PAID transactions in Jan and Feb 2026
        $t1 = Transaction::create(['code' => 'TRX-TEST-1', 'status' => 'PAID', 'transacted_at' => '2026-01-10 10:00:00', 'grand_total' => 100.00, 'subtotal' => 100.00]);
        TransactionItem::create(['transaction_id' => $t1->id, 'product_id' => $product->id, 'qty' => 1, 'price' => 100.00, 'line_total' => 100.00]);

        $t2 = Transaction::create(['code' => 'TRX-TEST-2', 'status' => 'PAID', 'transacted_at' => '2026-02-05 12:00:00', 'grand_total' => 200.00, 'subtotal' => 200.00]);
        TransactionItem::create(['transaction_id' => $t2->id, 'product_id' => $product->id, 'qty' => 2, 'price' => 100.00, 'line_total' => 200.00]);

        $resp = $this->getJson('/api/reports/transactions-monthly?year=2026');
        $resp->assertStatus(200);
        $json = $resp->json();

        $this->assertEquals(12, count($json['labels']));
        $this->assertEquals(12, count($json['values']));
        $this->assertEquals(1, $json['values'][0]); // Jan
        $this->assertEquals(1, $json['values'][1]); // Feb (one transaction)
    }

    public function test_category_share_requires_auth_and_admin_role()
    {
        $resp = $this->getJson('/api/reports/category-share');
        $resp->assertStatus(401);

        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);
        $this->getJson('/api/reports/category-share')->assertStatus(403);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->getJson('/api/reports/category-share')->assertStatus(200);
    }

    public function test_category_share_aggregation_and_percentages()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $catA = Category::factory()->create(['name' => 'A']);
        $catB = Category::factory()->create(['name' => 'B']);

        $pA = Product::factory()->create(['category_id' => $catA->id, 'selling_price' => 100]);
        $pB = Product::factory()->create(['category_id' => $catB->id, 'selling_price' => 50]);

        StockMovement::create(['product_id' => $pA->id, 'type' => 'IN', 'qty' => 10]);
        StockMovement::create(['product_id' => $pB->id, 'type' => 'IN', 'qty' => 10]);

        // Create PAID transactions and items within date range
        $t1 = Transaction::create(['code' => 'TRX-TEST-3', 'status' => 'PAID', 'transacted_at' => '2026-01-10 10:00:00', 'grand_total' => 200.00, 'subtotal' => 200.00]);
        TransactionItem::create(['transaction_id' => $t1->id, 'product_id' => $pA->id, 'qty' => 1, 'price' => 100.00, 'line_total' => 100.00]);
        TransactionItem::create(['transaction_id' => $t1->id, 'product_id' => $pB->id, 'qty' => 2, 'price' => 50.00, 'line_total' => 100.00]);

        $t2 = Transaction::create(['code' => 'TRX-TEST-4', 'status' => 'PAID', 'transacted_at' => '2026-01-11 12:00:00', 'grand_total' => 50.00, 'subtotal' => 50.00]);
        TransactionItem::create(['transaction_id' => $t2->id, 'product_id' => $pB->id, 'qty' => 1, 'price' => 50.00, 'line_total' => 50.00]);

        $resp = $this->getJson('/api/reports/category-share?start=2026-01-01&end=2026-01-31&limit=10');
        $resp->assertStatus(200);
        $json = $resp->json();

        $this->assertArrayHasKey('total_sum', $json);
        $this->assertEquals(250.0, $json['total_sum']);

        // Totals should include categories A and B
        $this->assertCount(2, $json['totals']);

        // Verify percentages sum to ~100
        $sumPercent = array_reduce($json['totals'], fn($carry, $t) => $carry + $t['percentage'], 0);
        $this->assertGreaterThan(99.9, $sumPercent);
    }

    public function test_category_share_limit_and_date_filtering()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $catA = Category::factory()->create(['name' => 'A']);
        $pA = Product::factory()->create(['category_id' => $catA->id, 'selling_price' => 100]);
        StockMovement::create(['product_id' => $pA->id, 'type' => 'IN', 'qty' => 10]);

        // Create one in range and one out of range
        $t1 = Transaction::create(['code' => 'TRX-TEST-5', 'status' => 'PAID', 'transacted_at' => '2026-02-01 10:00:00', 'grand_total' => 100.00, 'subtotal' => 100.00]);
        TransactionItem::create(['transaction_id' => $t1->id, 'product_id' => $pA->id, 'qty' => 1, 'price' => 100.00, 'line_total' => 100.00]);

        $t2 = Transaction::create(['code' => 'TRX-TEST-6', 'status' => 'PAID', 'transacted_at' => '2025-12-01 10:00:00', 'grand_total' => 100.00, 'subtotal' => 100.00]);
        TransactionItem::create(['transaction_id' => $t2->id, 'product_id' => $pA->id, 'qty' => 1, 'price' => 100.00, 'line_total' => 100.00]);

        $resp = $this->getJson('/api/reports/category-share?start=2026-02-01&end=2026-02-28&limit=1');
        $resp->assertStatus(200);
        $json = $resp->json();

        $this->assertEquals(100.0, $json['total_sum']);
        $this->assertCount(1, $json['totals']);
    }

    public function test_api_handles_sqlite_sum_types()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $cat = Category::factory()->create(['name' => 'A']);
        $p = Product::factory()->create(['category_id' => $cat->id, 'selling_price' => 99.99]);
        StockMovement::create(['product_id' => $p->id, 'type' => 'IN', 'qty' => 10]);

        $t = Transaction::create(['code' => 'TRX-TEST-7', 'status' => 'PAID', 'transacted_at' => '2026-03-01 10:00:00', 'grand_total' => 99.99, 'subtotal' => 99.99]);
        TransactionItem::create(['transaction_id' => $t->id, 'product_id' => $p->id, 'qty' => 1, 'price' => 99.99, 'line_total' => 99.99]);

        $resp = $this->getJson('/api/reports/category-share?start=2026-03-01&end=2026-03-31');
        $resp->assertStatus(200);
        $json = $resp->json();

        $this->assertIsFloat($json['total_sum']);
        foreach ($json['totals'] as $t) {
            $this->assertIsFloat($t['total']);
            // Percentage may encode as integer 100 in JSON; assert numeric instead
            $this->assertIsNumeric($t['percentage']);
        }
    }
}
