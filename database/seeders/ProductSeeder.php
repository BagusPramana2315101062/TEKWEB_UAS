<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run()
    {
        if (Category::count() === 0) {
            Category::factory()->count(5)->create();
        }

        Product::factory()->count(20)->create();

        $category = Category::first();

        Product::firstOrCreate(
            ['slug' => 'demo-product-1'],
            [
                'category_id' => $category->id,
                'name' => 'Demo Product 1',
                'description' => 'A demo product for presentation.',
                'purchase_price' => 10.00,
                'selling_price' => 20.00,
                'is_active' => true,
            ]
        );
    }
}
