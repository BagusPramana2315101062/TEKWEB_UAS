<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run()
    {
        Category::factory()->count(5)->create();

        Category::firstOrCreate(['slug' => 'electronics'], ['name' => 'Electronics']);
        Category::firstOrCreate(['slug' => 'books'], ['name' => 'Books']);
        Category::firstOrCreate(['slug' => 'clothing'], ['name' => 'Clothing']);
    }
}
