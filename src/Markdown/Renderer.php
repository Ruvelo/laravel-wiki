<?php

namespace Ruvelo\Wiki\Markdown;

use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\WikiLinks;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;

class Renderer
{
    /**
     * Markdown to HTML. Raw HTML in the source is escaped and javascript:
     * style links are dropped, so any editor's text is safe to display.
     */
    public function render(string $markdown): string
    {
        $slugs = WikiLinks::targets($markdown);
        $existing = $slugs === []
            ? []
            : array_flip(Page::query()->whereIn('slug', $slugs)->pluck('slug')->all());

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'symbol' => '#',
                'insert' => 'after',
                'title' => 'Link to this section',
            ],
            'table_of_contents' => [
                'position' => 'placeholder',
                'placeholder' => '[TOC]',
                'html_class' => 'wiki-toc',
            ],
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new TableOfContentsExtension);
        $environment->addInlineParser(new WikiLinkParser(fn (string $slug) => isset($existing[$slug])), 100);

        return (string) (new MarkdownConverter($environment))->convert($markdown);
    }
}
