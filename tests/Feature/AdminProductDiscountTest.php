<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;

class AdminProductDiscountTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_set_product_discount()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);

        $product = Product::factory()->create(['selling_price' => 100.00]);

        $resp = $this->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'purchase_price' => $product->purchase_price,
            'selling_price' => $product->selling_price,
            'discount_type' => 'PERCENT',
            'discount_value' => 20,
        ]);

        $resp->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'discount_type' => 'PERCENT',
            'discount_value' => 20.00,
        ]);

        $this->get(route('admin.products.index'))->assertSee('20% off');
    }

    public function test_admin_cannot_set_nominal_discount()
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
            'discount_type' => 'NOMINAL',
            'discount_value' => 10,
        ]);

        $resp->assertStatus(302);
        $resp->assertSessionHasErrors('discount_type');
        $this->assertDatabaseMissing('products', [ 'id' => $product->id, 'discount_type' => 'NOMINAL' ]);
    }
}
