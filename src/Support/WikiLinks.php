<?php

namespace FrancoisBultez\Lore\Support;

use FrancoisBultez\Lore\Models\Page;

final class WikiLinks
{
    /**
     * [[Target]] or [[Target|label]]. Shared with the Markdown parser so the
     * links we index are the links we render.
     */
    public const PATTERN = '\[\[([^\[\]|\n]+?)(?:\|([^\[\]\n]+?))?\]\]';

    /**
     * Slugs of every page the Markdown links to, skipping code.
     *
     * @return list<string>
     */
    public static function targets(string $markdown): array
    {
        $prose = preg_replace(['/^(`{3,}|~{3,}).*?^\1/ms', '/`+[^`\n]*`+/'], '', $markdown) ?? $markdown;

        preg_match_all('/'.self::PATTERN.'/u', $prose, $matches);

        $slugs = array_map(fn (string $target) => Page::slugFor($target), $matches[1]);

        return array_values(array_unique(array_filter($slugs, fn (string $slug) => $slug !== '')));
    }
}
