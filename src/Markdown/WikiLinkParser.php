<?php

namespace FrancoisBultez\Lore\Markdown;

use Closure;
use FrancoisBultez\Lore\Models\Page;
use FrancoisBultez\Lore\Support\WikiLinks;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Turns [[Target]] and [[Target|label]] into links. Links to pages that don't
 * exist yet get the `lore-link--new` class and point at the create form.
 */
final class WikiLinkParser implements InlineParserInterface
{
    /**
     * @param  Closure(string): bool  $exists  given a slug
     */
    public function __construct(private readonly Closure $exists) {}

    public function getMatchDefinition(): InlineParserMatch
    {
        return InlineParserMatch::regex(WikiLinks::PATTERN);
    }

    public function parse(InlineParserContext $inlineContext): bool
    {
        [$target, $label] = array_pad($inlineContext->getSubMatches(), 2, null);

        $target = trim($target);
        $slug = Page::slugFor($target);

        if ($slug === '') {
            return false;
        }

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());

        $exists = ($this->exists)($slug);
        $url = $exists
            ? route('lore.show', $slug)
            : route('lore.create', ['title' => $target]);

        $link = new Link($url, trim($label ?? '') ?: $target);
        $link->data->set('attributes/class', $exists ? 'lore-link' : 'lore-link lore-link--new');

        $inlineContext->getContainer()->appendChild($link);

        return true;
    }
}
