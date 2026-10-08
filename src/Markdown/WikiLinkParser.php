<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Markdown;

use Closure;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Parser\Inline\InlineParserInterface;
use League\CommonMark\Parser\Inline\InlineParserMatch;
use League\CommonMark\Parser\InlineParserContext;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\WikiLinks;
use Ruvelo\Wiki\Wiki;

/**
 * Turns [[Target]], [[Target|label]] and [[Target#Section]] into links.
 *
 * Links to pages that don't exist yet get the `wiki-link--new` class; they
 * open the create form for people who can edit, and the "no page here yet"
 * page for everyone else.
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

        ['page' => $title, 'section' => $section] = WikiLinks::parseTarget((string) $target);
        $slug = Page::slugFor($title);
        $fragment = $section === null ? '' : '#'.WikiLinks::sectionId($section);

        if ($slug === '' && $fragment === '') {
            return false;
        }

        $inlineContext->getCursor()->advanceBy($inlineContext->getFullMatchLength());

        $text = trim((string) $label) ?: trim(implode(' › ', array_filter([$title, $section])));

        if ($slug === '') {
            // [[#Section]]: a link within the current page.
            $link = new Link($fragment, $text);
            $link->data->set('attributes/class', 'wiki-link');
            $inlineContext->getContainer()->appendChild($link);

            return true;
        }

        $exists = ($this->exists)($slug);
        $url = $exists || ! Wiki::canEdit()
            ? route('wiki.show', $slug).$fragment
            : route('wiki.create', ['title' => $title]);

        $link = new Link($url, $text);
        $link->data->set('attributes/class', $exists ? 'wiki-link' : 'wiki-link wiki-link--new');

        $inlineContext->getContainer()->appendChild($link);

        return true;
    }
}
