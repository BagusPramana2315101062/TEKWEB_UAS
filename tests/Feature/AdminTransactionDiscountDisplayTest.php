<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class AdminTransactionDiscountDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_show_displays_per_line_discount()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 80.00, 'discount_type' => 'PERCENT', 'discount_value' => 25]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $payload = [ 'items' => [ ['product_id' => $product->id, 'qty' => 2] ] ];
        $resp = $this->postJson('/api/transactions', $payload);
        $resp->assertStatus(201);
        $trxId = $resp->json()['transaction_id'];

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $show = $this->get('/admin/transactions/' . $trxId);
        $show->assertStatus(200);

        // Expect discount label (25% and nominal amount show in items table)
        $show->assertSee('25.00%');
        $show->assertSee('Rp 40.00');
    }
}
