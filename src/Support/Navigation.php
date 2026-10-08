<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Support;

use Ruvelo\Wiki\Models\Page;

/**
 * The left menu and the "On this page" outline.
 *
 * The menu is an ordinary page (slug `wiki.sidebar`, "sidebar" by default):
 * headings become groups and [[links]] become items, like a GitHub wiki's
 * _Sidebar. Without that page, the menu lists pages alphabetically.
 */
final class Navigation
{
    /** Pages listed in the automatic menu before "All pages" takes over. */
    private const AUTO_LIMIT = 40;

    public static function sidebarSlug(): ?string
    {
        $slug = config('wiki.sidebar', 'sidebar');

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    /**
     * The menu's HTML, with the link to $currentSlug marked as current.
     */
    public static function sidebar(?string $currentSlug = null): string
    {
        $slug = self::sidebarSlug();
        $page = $slug === null ? null : Page::query()->where('slug', $slug)->first();

        $html = $page !== null && trim($page->body) !== '' ? $page->html() : self::automatic();

        if ($currentSlug === null) {
            return $html;
        }

        $href = 'href="'.e(route('wiki.show', $currentSlug)).'"';

        return str_replace($href, $href.' aria-current="page"', $html);
    }

    /**
     * The h2 and h3 headings of rendered page HTML, for the outline.
     *
     * @return list<array{level: int, id: string, text: string}>
     */
    public static function outline(string $html): array
    {
        preg_match_all('/<h([23])>(.*?)<a id="([^"]+)"/s', $html, $matches, PREG_SET_ORDER);

        return array_map(fn (array $match) => [
            'level' => (int) $match[1],
            'id' => html_entity_decode($match[3], ENT_QUOTES),
            'text' => trim(html_entity_decode(strip_tags($match[2]), ENT_QUOTES)),
        ], $matches);
    }

    private static function automatic(): string
    {
        $pages = Page::query()
            ->when(self::sidebarSlug(), fn ($query, string $slug) => $query->where('slug', '!=', $slug))
            ->orderBy('title')
            ->limit(self::AUTO_LIMIT + 1)
            ->get(['title', 'slug']);

        $items = $pages->take(self::AUTO_LIMIT)
            ->map(fn (Page $page) => '<li><a href="'.e(route('wiki.show', $page)).'">'.e($page->title).'</a></li>')
            ->implode('');

        if ($pages->count() > self::AUTO_LIMIT) {
            $items .= '<li><a href="'.e(route('wiki.index')).'">All pages…</a></li>';
        }

        return $items === '' ? '' : "<h2>Pages</h2><ul>{$items}</ul>";
    }
}
