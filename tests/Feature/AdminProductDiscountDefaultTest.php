<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;

class AdminProductDiscountDefaultTest extends TestCase
{
    use RefreshDatabase;

    public function test_discount_value_without_type_defaults_to_percent()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::factory()->create();

        $resp = $this->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            // omit discount_type, provide only discount_value
            'discount_value' => 25,
        ]);

        $resp->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'discount_type' => 'PERCENT',
            'discount_value' => 25.00,
        ]);

        $this->get(route('admin.products.index'))->assertSee('25% off');
    }
}
