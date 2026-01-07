<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiTransactionDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_transaction_applies_percent_discount_and_tax_after_discount()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 50.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        // Note: tax_rate is not specified — default rate from config/tax.php should be used
        config(['tax.default_rate' => 10]);

        $payload = [
            'items' => [ ['product_id' => $product->id, 'qty' => 2] ],
            'discount_type' => 'PERCENT',
            'discount_value' => 10,
        ];

        $response = $this->postJson('/api/transactions', $payload);

        $response->assertStatus(201);
        $json = $response->json();

        // subtotal = 100, discount 10% => 10, taxable = 90, tax = 9, grand_total = 100 - 10 + 9 = 99
        $this->assertEquals(99.00, $json['grand_total']);
        $this->assertEquals(10.00, $json['discount_nominal']);
        $this->assertEquals(9.00, $json['tax_amount']);

        $this->assertDatabaseHas('transactions', [ 'id' => $json['transaction_id'], 'tax_amount' => 9.00, 'grand_total' => 99.00 ]);
    }
}