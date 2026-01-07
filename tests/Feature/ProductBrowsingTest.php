<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Product;
use App\Models\Category;
use App\Models\User;
use App\Models\StockMovement;

class ProductBrowsingTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_and_search()
    {
        $catA = Category::factory()->create(['name' => 'A']);
        $catB = Category::factory()->create(['name' => 'B']);

        $p1 = Product::factory()->create(['name' => 'Apple', 'category_id' => $catA->id, 'selling_price' => 10, 'is_active' => true]);
        $p2 = Product::factory()->create(['name' => 'Banana', 'category_id' => $catB->id, 'selling_price' => 20, 'is_active' => true]);

        $resp = $this->get(route('products.index'));
        $resp->assertStatus(200)->assertSee('Apple')->assertSee('Banana');

        $resp = $this->get(route('products.index', ['q' => 'Apple']));
        $resp->assertStatus(200)->assertSee('Apple')->assertDontSee('Banana');
    }

    public function test_product_show_and_comments_via_api()
    {
        $this->markTestSkipped('Comments feature disabled for this project.');

        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Orange']);

        $this->actingAs($user)->postJson('/api/comments', [
            'commentable_type' => \App\Models\Product::class,
            'commentable_id' => $product->id,
            'content' => 'Nice product'
        ])->assertStatus(201)->assertJsonFragment(['content' => 'Nice product']);

        $get = $this->getJson('/api/comments/' . $product->id . '?commentable_type=' . urlencode(\App\Models\Product::class));
        $get->assertStatus(200);
        $json = $get->json();
        $this->assertCount(1, $json['comments']);
        $this->assertEquals('Nice product', $json['comments'][0]['content']);
    }
}
