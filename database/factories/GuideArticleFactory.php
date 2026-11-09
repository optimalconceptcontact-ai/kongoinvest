<?php

namespace Database\Factories;

use App\Models\GuideArticle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GuideArticle>
 */
class GuideArticleFactory extends Factory
{
    protected $model = GuideArticle::class;

    public function definition(): array
    {
        return [
            'section' => 'glossary',
            'title' => fake()->unique()->sentence(3),
            'description' => fake()->sentence(),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'order' => 0,
            'is_published' => true,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn () => ['is_published' => false]);
    }
}
