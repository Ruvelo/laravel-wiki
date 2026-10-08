<?php

namespace Ruvelo\Wiki\Markdown;

use Closure;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\WikiLinks;
use Ruvelo\Wiki\Wiki;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;

/**
 * Turns [[Target]] and [[Target|label]] into links. Links to pages that don't
 * exist yet get the `wiki-link--new` class; they open the create form for
 * people who can edit, and the "no page here yet" page for everyone else.
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
        $url = $exists || ! Wiki::canEdit()
            ? route('wiki.show', $slug)
            : route('wiki.create', ['title' => $target]);

        $link = new Link($url, trim($label ?? '') ?: $target);
        $link->data->set('attributes/class', $exists ? 'wiki-link' : 'wiki-link wiki-link--new');

        $inlineContext->getContainer()->appendChild($link);

        return true;
    }
}
