<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Travel', 'slug' => 'travel', 'description' => 'Travel vlogs and adventures', 'sort_order' => 1],
            ['name' => 'Food', 'slug' => 'food', 'description' => 'Food and cooking vlogs', 'sort_order' => 2],
            ['name' => 'Technology', 'slug' => 'technology', 'description' => 'Tech reviews and tutorials', 'sort_order' => 3],
            ['name' => 'Lifestyle', 'slug' => 'lifestyle', 'description' => 'Lifestyle and daily vlogs', 'sort_order' => 4],
            ['name' => 'Gaming', 'slug' => 'gaming', 'description' => 'Gaming content and walkthroughs', 'sort_order' => 5],
            ['name' => 'Education', 'slug' => 'education', 'description' => 'Educational content and tutorials', 'sort_order' => 6],
            ['name' => 'Entertainment', 'slug' => 'entertainment', 'description' => 'Entertainment and comedy', 'sort_order' => 7],
            ['name' => 'Fitness', 'slug' => 'fitness', 'description' => 'Fitness and workout vlogs', 'sort_order' => 8],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
