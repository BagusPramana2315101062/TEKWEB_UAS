<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\StockMovement;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_buy()
    {
        $product = Product::factory()->create();

        $resp = $this->post(route('checkout.buy'), ['product_id' => $product->id, 'qty' => 1]);
        $resp->assertRedirect(route('login'));
    }

    public function test_user_can_buy_product_and_transaction_created()
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        // create stock
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 5, 'ref_type' => 'seed', 'ref_id' => null]);

        $resp = $this->actingAs($user)->post(route('checkout.buy'), ['product_id' => $product->id, 'qty' => 2]);

        $resp->assertRedirect();
        $this->assertDatabaseHas('transactions', ['user_id' => $user->id]);
        $this->assertDatabaseHas('transaction_items', ['product_id' => $product->id, 'qty' => 2]);
        $this->assertDatabaseHas('stock_movements', ['product_id' => $product->id, 'type' => 'OUT', 'qty' => 2]);
    }
}
