<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\Category;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        $name = $this->faker->unique()->words(2, true);

        return [
            'category_id'    => Category::factory(),
            'name'           => ucfirst($name),
            'slug'           => Str::slug($name) . '-' . $this->faker->randomNumber(3),
            'description'    => $this->faker->sentence(),
            'purchase_price' => $this->faker->randomFloat(2, 1, 500),
            'selling_price'  => $this->faker->randomFloat(2, 10, 1000),
            'is_active'      => $this->faker->boolean(90),
        ];
    }
}
