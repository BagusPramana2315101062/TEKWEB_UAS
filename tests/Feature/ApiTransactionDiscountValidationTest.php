<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class ApiTransactionDiscountValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_percent_over_100_is_rejected()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 10.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $payload = [
            'items' => [ ['product_id' => $product->id, 'qty' => 1] ],
            'discount_type' => 'PERCENT',
            'discount_value' => 150,
        ];

        $resp = $this->postJson('/api/transactions', $payload);
        $resp->assertStatus(422);
        $resp->assertJsonStructure(['errors' => ['discount_value']]);
        $this->assertStringContainsString('must be between 0 and 100', $resp->json('errors.discount_value.0'));
    }

    public function test_negative_discount_is_rejected()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $product = Product::factory()->create(['selling_price' => 10.00]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $payload = [
            'items' => [ ['product_id' => $product->id, 'qty' => 1] ],
            'discount_type' => 'NOMINAL',
            'discount_value' => -5,
        ];

        $resp = $this->postJson('/api/transactions', $payload);
        $resp->assertStatus(422);
        $resp->assertJsonStructure(['errors' => ['discount_value']]);
        $this->assertStringContainsString('at least 0', $resp->json('errors.discount_value.0'));
    }
}