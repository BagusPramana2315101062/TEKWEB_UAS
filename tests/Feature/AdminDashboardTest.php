<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;
use App\Services\TransactionService;
use Carbon\Carbon;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_dashboard_and_sees_canvas()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);
        $resp->assertSee('<canvas id="transactionsChart"', false);
    }

    public function test_non_admin_forbidden()
    {
        $user = User::factory()->create(['role' => 'user']);
        $this->actingAs($user);

        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(403);
    }

    public function test_dataset_updates_after_creating_transaction()
    {
        // Fix the current time to ensure deterministic month index
        Carbon::setTestNow(Carbon::now());
        $currentMonthIndex = (int) Carbon::now()->format('n') - 1;

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::factory()->create(['selling_price' => 100.00]);
        StockMovement::create([
            'product_id' => $product->id,
            'type' => 'IN',
            'qty' => 5,
            'notes' => 'Initial stock',
        ]);

        // Initially no transactions: dashboard values for current month should be 0
        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);
        $content = $resp->getContent();
        preg_match('/const values = (\[.*?\]);/s', $content, $m);
        $this->assertNotEmpty($m[1], 'Values array not found in dashboard HTML');
        $values = json_decode($m[1], true);
        $this->assertIsArray($values);
        $this->assertEquals(0, $values[$currentMonthIndex]);

        // Create a PAID transaction through TransactionService
        $this->app->make(TransactionService::class)->createTransaction($admin->id, [
            ['product_id' => $product->id, 'qty' => 1]
        ]);

        // Re-request dashboard and ensure current month increased
        $resp = $this->get('/admin/dashboard');
        $resp->assertStatus(200);
        $content = $resp->getContent();
        preg_match('/const values = (\[.*?\]);/s', $content, $m2);
        $valuesAfter = json_decode($m2[1], true);
        $this->assertEquals(1, $valuesAfter[$currentMonthIndex]);
    }
}
