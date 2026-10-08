<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Mcp;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Models\Revision;

/**
 * How pages and revisions look to an AI agent: plain arrays, with the web
 * address and the resource URI so it can cite or fetch them.
 *
 * @internal
 */
final class Present
{
    /**
     * @return array{title: string, slug: string, url: string|null, uri: string, updated_at: string}
     */
    public static function summary(Page $page): array
    {
        return [
            'title' => $page->title,
            'slug' => $page->slug,
            'url' => self::url($page->slug),
            'uri' => self::uri($page->slug),
            'updated_at' => $page->updated_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function page(Page $page): array
    {
        $outgoing = $page->outgoingLinks();
        $existing = Page::query()->whereIn('slug', $outgoing)->pluck('title', 'slug');

        return self::summary($page) + [
            'revision' => $page->currentRevisionId(),
            'body' => $page->body,
            'links' => array_map(fn (string $slug): array => [
                'slug' => $slug,
                'title' => $existing[$slug] ?? null,
                'exists' => isset($existing[$slug]),
            ], $outgoing),
            'backlinks' => $page->backlinks()->orderBy('title')->get(['title', 'slug'])
                ->map(fn (Page $from): array => ['title' => $from->title, 'slug' => $from->slug])
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function revision(Revision $revision): array
    {
        $page = $revision->page;

        return [
            'revision' => $revision->id,
            'title' => $revision->title,
            'slug' => $page?->slug,
            'url' => $page === null ? null : self::url($page->slug),
            'summary' => $revision->summary,
            'author' => $revision->authorName(),
            'created_at' => $revision->created_at->toIso8601String(),
        ];
    }

    public static function snippet(Page $page, string $query): string
    {
        $text = $page->plainText();

        return Str::excerpt($text, $query, ['radius' => 120]) ?? Str::limit($text, 240);
    }

    public static function uri(string $slug): string
    {
        return 'wiki://pages/'.rawurlencode($slug);
    }

    /**
     * Null when the host app registers its own routes without the names.
     */
    public static function url(string $slug): ?string
    {
        return Route::has('wiki.show') ? route('wiki.show', $slug) : null;
    }
}
