<?php

namespace Ruvelo\Wiki\Tests\Feature;

use Ruvelo\Wiki\Markdown\Renderer;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Support\WikiLinks;
use Ruvelo\Wiki\Tests\TestCase;

class RenderingTest extends TestCase
{
    private function render(string $markdown): string
    {
        return app(Renderer::class)->render($markdown);
    }

    public function test_wiki_links_point_at_existing_pages_and_red_link_missing_ones(): void
    {
        (new Page(['slug' => 'deploy-guide']))->commit('Deploy guide', 'How we ship.');

        $html = $this->render('See [[Deploy guide]] and [[On-call rota|the rota]].');

        $this->assertStringContainsString('<a class="wiki-link" href="'.route('wiki.show', 'deploy-guide').'">Deploy guide</a>', $html);
        $this->assertStringContainsString('<a class="wiki-link wiki-link--new" href="'.route('wiki.create', ['title' => 'On-call rota']).'">the rota</a>', $html);
    }

    public function test_wiki_links_inside_code_are_left_alone(): void
    {
        $html = $this->render("`[[Not a link]]`\n\n```\n[[Nor this]]\n```");

        $this->assertStringNotContainsString('<a', $html);
        $this->assertSame([], WikiLinks::targets("`[[Not a link]]`\n\n```\n[[Nor this]]\n```"));
    }

    public function test_raw_html_and_javascript_links_are_neutralised(): void
    {
        $html = $this->render("<script>alert(1)</script>\n\n[click](javascript:alert(1))");

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_github_flavoured_markdown_and_table_of_contents(): void
    {
        $html = $this->render("[TOC]\n\n## Setup\n\n| a | b |\n|---|---|\n| 1 | 2 |\n\n- [x] done");

        $this->assertStringContainsString('class="wiki-toc"', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringContainsString('type="checkbox"', $html);
        $this->assertStringContainsString('heading-permalink', $html);
    }

    public function test_unicode_titles_keep_readable_slugs(): void
    {
        $this->assertSame('café-crème', Page::slugFor('Café Crème'));
        $this->assertSame('日本語', Page::slugFor('日本語'));
    }
}
