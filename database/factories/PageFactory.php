<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Ruvelo\Wiki\Models\Page;

/**
 * For your own tests: `Page::factory()->create()` or
 * `Page::factory()->linkingTo('Deploy guide')->create()`.
 *
 * Created pages get a first revision and indexed links, like real ones.
 *
 * @extends Factory<Page>
 */
final class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(3), '.');

        return [
            'title' => $title,
            'slug' => Page::slugFor($title),
            'body' => $this->faker->paragraphs(3, true),
        ];
    }

    public function titled(string $title): self
    {
        return $this->state(['title' => $title, 'slug' => Page::slugFor($title)]);
    }

    public function linkingTo(string ...$titles): self
    {
        return $this->state(fn (array $attributes) => [
            'body' => $attributes['body']."\n\nSee ".implode(', ', array_map(fn ($title) => "[[{$title}]]", $titles)).'.',
        ]);
    }

    public function configure(): self
    {
        return $this->afterCreating(fn (Page $page) => $page->commit($page->title, $page->body, 'Created page'));
    }
}
