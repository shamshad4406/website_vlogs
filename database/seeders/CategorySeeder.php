<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Travel', 'icon' => 'map-pin', 'color' => '#0E7490', 'description' => 'Trips, destinations and road stories from across the country and beyond.'],
            ['name' => 'Food', 'icon' => 'utensils', 'color' => '#C2410C', 'description' => 'Street food, home kitchens, recipes and restaurant finds.'],
            ['name' => 'Lifestyle', 'icon' => 'sparkles', 'color' => '#9333EA', 'description' => 'Daily life, routines, home, style and everything in between.'],
            ['name' => 'Technology', 'icon' => 'cpu', 'color' => '#1D4ED8', 'description' => 'Hands-on reviews, builds, workflows and plain-language explainers.'],
            ['name' => 'Gaming', 'icon' => 'gamepad', 'color' => '#BE123C', 'description' => 'Playthroughs, setups and community moments.'],
            ['name' => 'Education', 'icon' => 'book-open', 'color' => '#047857', 'description' => 'Learn something in the time it takes to drink a coffee.'],
            ['name' => 'Entertainment', 'icon' => 'film', 'color' => '#DB2777', 'description' => 'Comedy, culture, music and things worth watching twice.'],
            ['name' => 'Fitness', 'icon' => 'activity', 'color' => '#B45309', 'description' => 'Training, running, yoga and getting started without the hype.'],
        ];

        foreach ($categories as $i => $category) {
            Category::updateOrCreate(
                ['slug' => str($category['name'])->slug()->toString()],
                $category + [
                    'slug' => str($category['name'])->slug()->toString(),
                    'sort_order' => $i + 1,
                    'is_active' => true,
                    'meta_title' => $category['name'].' vlogs',
                    'meta_description' => $category['description'],
                ]
            );
        }

        $this->command->info('Seeded '.count($categories).' categories.');
    }
}
