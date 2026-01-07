<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\StockMovement;

class ApiProductDiscountExposureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_show_json_includes_discount_and_discounted_price_for_percent()
    {
        $product = Product::factory()->create(['selling_price' => 200.00, 'discount_type' => 'PERCENT', 'discount_value' => 25]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $response = $this->getJson('/products/' . $product->slug);
        $response->assertStatus(200);
        $json = $response->json();

        // discounted price = 200 - 25% = 150.00
        $this->assertEquals(150.00, $json['discounted_price']);
        $this->assertEquals('PERCENT', $json['discount_type']);
        $this->assertEquals(25.00, $json['discount_value']);
    }

    public function test_product_show_json_includes_discount_and_discounted_price_for_nominal()
    {
        $product = Product::factory()->create(['selling_price' => 120.50, 'discount_type' => 'NOMINAL', 'discount_value' => 10.50]);
        StockMovement::create(['product_id' => $product->id, 'type' => 'IN', 'qty' => 10, 'notes' => 'seed']);

        $response = $this->getJson('/products/' . $product->slug);
        $response->assertStatus(200);
        $json = $response->json();

        // discounted price = 120.50 - 10.50 = 110.00
        $this->assertEquals(110.00, $json['discounted_price']);
        $this->assertEquals('NOMINAL', $json['discount_type']);
        $this->assertEquals(10.50, $json['discount_value']);
    }
}
