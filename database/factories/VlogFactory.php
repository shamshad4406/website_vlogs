<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use App\Models\Vlog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Vlog> */
class VlogFactory extends Factory
{
    protected $model = Vlog::class;

    public function definition(): array
    {
        $title = Str::title(fake()->unique()->sentence(5));

        return [
            'creator_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(5),
            'description' => fake()->paragraph(),
            'video_file' => 'videos/sample.mp4',
            'thumbnail' => 'thumbnails/sample.jpg',
            'duration_seconds' => fake()->numberBetween(60, 1800),
            'language' => 'en',
            'visibility' => 'public',
            'allow_comments' => true,
            'status' => 'published',
            'published_at' => now()->subDay(),
            'views_count' => 0,
            'likes_count' => 0,
            'comments_count' => 0,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => 'draft', 'published_at' => null]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending', 'published_at' => null]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => 'rejected',
            'published_at' => null,
            'rejection_reason' => 'The audio is too quiet.',
        ]);
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['status' => 'unpublished']);
    }

    public function private(): static
    {
        return $this->state(fn () => ['visibility' => 'private']);
    }

    public function unlisted(): static
    {
        return $this->state(fn () => ['visibility' => 'unlisted']);
    }

    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => now()->addWeek(),
            'scheduled_at' => now()->addWeek(),
        ]);
    }
}
