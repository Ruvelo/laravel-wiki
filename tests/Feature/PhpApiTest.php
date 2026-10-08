<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature;

use Illuminate\Support\Facades\Event;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use Ruvelo\Wiki\Events\PageDeleted;
use Ruvelo\Wiki\Events\PageSaved;
use Ruvelo\Wiki\Exceptions\EditConflict;
use Ruvelo\Wiki\Exceptions\InvalidTitle;
use Ruvelo\Wiki\Exceptions\PageAlreadyExists;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Tests\TestCase;
use Ruvelo\Wiki\Wiki;

class PhpApiTest extends TestCase
{
    protected function tearDown(): void
    {
        Wiki::flush();

        parent::tearDown();
    }

    public function test_create_find_and_write(): void
    {
        $author = $this->user();

        $page = Wiki::create('Release notes', '## 2.0', author: $author);

        $this->assertTrue(Wiki::find('release notes')->is($page));
        $this->assertTrue(Wiki::find('release-notes')->is($page));
        $this->assertNull(Wiki::find('Nope'));
        $this->assertSame((string) $author->id, $page->revisions()->first()->user_id);

        Wiki::write('Release notes', '## 2.1', 'Bump');
        Wiki::write('Release notes', '## 2.1', 'Same again');

        $this->assertSame('## 2.1', $page->fresh()->body);
        $this->assertSame(['Bump', 'Created page'], $page->revisions()->pluck('summary')->all());
    }

    public function test_write_creates_missing_pages(): void
    {
        $page = Wiki::write('Fresh page', 'Hello');

        $this->assertTrue($page->exists);
        $this->assertSame('Created page', $page->revisions()->value('summary'));
    }

    public function test_create_refuses_duplicates_and_empty_titles(): void
    {
        Wiki::create('Ops');

        $this->expectException(PageAlreadyExists::class);
        Wiki::create('OPS');
    }

    public function test_titles_need_a_letter_or_number(): void
    {
        $this->expectException(InvalidTitle::class);
        Wiki::create('!!!');
    }

    public function test_commit_refuses_stale_edits(): void
    {
        $page = Wiki::create('Ops', 'v1');
        $base = $page->currentRevisionId();
        $page->commit('Ops', 'v2');

        try {
            $page->commit('Ops', 'v3', basedOn: $base);
            $this->fail('Expected an edit conflict.');
        } catch (EditConflict $e) {
            $this->assertSame($base, $e->expectedRevision);
            $this->assertSame($page->currentRevisionId(), $e->currentRevision);
        }

        $this->assertSame('v2', $page->fresh()->body);
    }

    public function test_search_and_render(): void
    {
        Wiki::create('Deploy guide', 'Ship it.');
        Wiki::create('Cron', 'Deploy every minute.');

        $this->assertSame(['Deploy guide', 'Cron'], Wiki::search('deploy')->pluck('title')->all());
        $this->assertStringContainsString('<strong>hi</strong>', Wiki::render('**hi**'));
    }

    public function test_markdown_can_be_extended(): void
    {
        $this->assertStringNotContainsString('footnote', Wiki::render("Hi[^1]\n\n[^1]: There"));

        Wiki::extendMarkdown(fn (Environment $env) => $env->addExtension(new FootnoteExtension));

        $this->assertStringContainsString('footnote', Wiki::render("Hi[^1]\n\n[^1]: There"));
    }

    public function test_extensions_can_come_from_config(): void
    {
        config(['wiki.markdown.extensions' => [FootnoteExtension::class]]);
        Wiki::flush();

        $this->assertStringContainsString('footnote', Wiki::render("Hi[^1]\n\n[^1]: There"));
    }

    public function test_events(): void
    {
        Event::fake([PageSaved::class, PageDeleted::class]);

        $page = Wiki::create('Ops');
        $page->commit('Ops', 'v2');
        $page->delete();

        Event::assertDispatchedTimes(PageSaved::class, 2);
        Event::assertDispatched(PageDeleted::class, fn (PageDeleted $e) => $e->page->slug === 'ops');
    }

    public function test_was_created_tells_first_saves_apart(): void
    {
        $created = [];
        Event::listen(PageSaved::class, function (PageSaved $e) use (&$created): void {
            $created[] = $e->wasCreated();
        });

        Wiki::create('Ops')->commit('Ops', 'v2');

        $this->assertSame([true, false], $created);
    }

    public function test_the_factory_makes_real_pages(): void
    {
        $target = Page::factory()->titled('Deploy guide')->create();
        $page = Page::factory()->linkingTo('Deploy guide', 'Nowhere')->create();

        $this->assertSame(1, $page->revisions()->count());
        $this->assertSame(['deploy-guide', 'nowhere'], $page->outgoingLinks());
        $this->assertTrue($target->backlinks()->first()->is($page));
    }
}
