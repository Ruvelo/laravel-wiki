<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Support;

use Illuminate\Support\Str;
use Ruvelo\Wiki\Models\Page;

final class WikiLinks
{
    /**
     * [[Target]], [[Target|label]], [[Target#Section]] and [[#Section]], the
     * syntax Obsidian and most wikis share. Used by both the indexer and the
     * renderer, so the links we index are the links we render.
     */
    public const PATTERN = '\[\[([^\[\]|\n]+?)(?:\|([^\[\]\n]+?))?\]\]';

    /**
     * Split a link target into its page and section parts.
     *
     * @return array{page: string, section: string|null}
     */
    public static function parseTarget(string $target): array
    {
        $parts = explode('#', $target, 2);
        $section = trim($parts[1] ?? '');

        return ['page' => trim($parts[0]), 'section' => $section === '' ? null : $section];
    }

    /**
     * The id CommonMark gives a heading, so [[Page#Section]] lands on it.
     */
    public static function sectionId(string $section): string
    {
        return 'content-'.Str::slug($section, '-', null);
    }

    /**
     * Slugs of every page the Markdown links to, skipping code.
     *
     * @return list<string>
     */
    public static function targets(string $markdown): array
    {
        $prose = preg_replace(['/^(`{3,}|~{3,}).*?^\1/ms', '/`+[^`\n]*`+/'], '', $markdown) ?? $markdown;

        preg_match_all('/'.self::PATTERN.'/u', $prose, $matches);

        $slugs = array_map(fn (string $target) => Page::slugFor(self::parseTarget($target)['page']), $matches[1]);

        return array_values(array_unique(array_filter($slugs, fn (string $slug) => $slug !== '')));
    }
}
