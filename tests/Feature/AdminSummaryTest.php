<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AdminSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_requires_auth_and_admin_role()
    {
        $this->getJson('/api/admin/summary')->assertStatus(401);

        $user = User::factory()->create(['role' => 'user']);
        Sanctum::actingAs($user);
        $this->getJson('/api/admin/summary')->assertStatus(403);

        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);
        $this->getJson('/api/admin/summary')->assertStatus(200);
    }

    public function test_summary_counts_include_completed_transactions_and_monthly()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($admin);

        $product = \App\Models\Product::factory()->create(['selling_price' => 50.00]);
        \App\Models\StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10]);

        // create transactions with COMPLETED status within this week and in January 2026
        $t1 = \App\Models\Transaction::create(['code' => 'TRX-C-1', 'status' => 'COMPLETED', 'transacted_at' => now()->toDateTimeString(), 'grand_total' => 50.00, 'subtotal' => 50.00]);
        \App\Models\TransactionItem::create(['transaction_id' => $t1->id, 'product_id' => $product->id, 'qty' => 1, 'price' => 50.00, 'line_total' => 50.00]);

        $t2 = \App\Models\Transaction::create(['code' => 'TRX-C-2', 'status' => 'COMPLETED', 'transacted_at' => '2026-01-10 10:00:00', 'grand_total' => 50.00, 'subtotal' => 50.00]);
        \App\Models\TransactionItem::create(['transaction_id' => $t2->id, 'product_id' => $product->id, 'qty' => 1, 'price' => 50.00, 'line_total' => 50.00]);

        // summary endpoint should count the one in this week
        $resp = $this->getJson('/api/admin/summary');
        $resp->assertStatus(200);
        $json = $resp->json();
        $this->assertGreaterThanOrEqual(1, $json['transactions_this_week']);

        // transactions monthly should include the January entry
        $resp2 = $this->getJson('/api/reports/transactions-monthly?year=2026');
        $resp2->assertStatus(200);
        $json2 = $resp2->json();
        $this->assertEquals(12, count($json2['labels']));
        $this->assertEquals(12, count($json2['values']));
        $this->assertGreaterThanOrEqual(1, $json2['values'][0]);
    }
}