<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Markdown;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\TableOfContents\TableOfContentsExtension;
use League\CommonMark\MarkdownConverter;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\WikiLinks;
use Ruvelo\Wiki\Wiki;

/**
 * Markdown to HTML. Raw HTML in the source is escaped and javascript:-style
 * links are dropped, so any editor's text is safe to display.
 *
 * The CommonMark pipeline is built once and reused; each render only runs
 * one query, to learn which linked pages exist.
 */
class Renderer
{
    private ?MarkdownConverter $converter = null;

    /** @var array<string, true> */
    private array $existing = [];

    public function render(string $markdown): string
    {
        $slugs = WikiLinks::targets($markdown);

        $this->existing = $slugs === []
            ? []
            : array_fill_keys(Page::query()->whereIn('slug', $slugs)->pluck('slug')->all(), true);

        return (string) $this->converter()->convert($markdown);
    }

    /**
     * Rebuild the pipeline on next use, e.g. after adding an extension.
     */
    public function reset(): void
    {
        $this->converter = null;
    }

    private function converter(): MarkdownConverter
    {
        return $this->converter ??= $this->build();
    }

    private function build(): MarkdownConverter
    {
        $environment = new Environment(array_replace_recursive([
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
        ], config('wiki.markdown.options', [])));

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new GithubFlavoredMarkdownExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new TableOfContentsExtension);

        foreach (config('wiki.markdown.extensions', []) as $extension) {
            /** @var ExtensionInterface $extension */
            $extension = is_string($extension) ? app($extension) : $extension;
            $environment->addExtension($extension);
        }

        $environment->addInlineParser(new WikiLinkParser(fn (string $slug): bool => isset($this->existing[$slug])), 100);

        foreach (Wiki::markdownExtenders() as $extend) {
            $extend($environment);
        }

        return new MarkdownConverter($environment);
    }
}
