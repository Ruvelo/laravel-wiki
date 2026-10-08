<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature\Mcp;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Ruvelo\Wiki\Events\PageSaved;
use Ruvelo\Wiki\Mcp\LocalWikiServer;
use Ruvelo\Wiki\Mcp\Tools\WritePage;
use Ruvelo\Wiki\Mcp\WikiServer;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Wiki;

class WritePageTest extends McpTestCase
{
    private function allowWrites(): void
    {
        $this->app['config']->set('wiki.mcp.allow_writes', true);
    }

    public function test_writing_is_off_unless_the_config_allows_it(): void
    {
        WikiServer::actingAs($this->user())
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'Start'])
            ->assertHasErrors(['Tool [write_page] not found']);

        $this->assertNull(Wiki::find('Ops'));

        $this->allowWrites();
        WikiServer::tools()->assertRegistered(WritePage::class);
    }

    public function test_create_a_page(): void
    {
        $this->allowWrites();
        Event::fake([PageSaved::class]);
        $maya = $this->user('Maya');

        WikiServer::actingAs($maya)
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => "On call\r\nrota", 'summary' => 'Start the ops page'])
            ->assertHasNoErrors()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('created', true)
                ->where('changed', true)
                ->where('page.slug', 'ops')
                ->where('page.url', route('wiki.show', 'ops'))
                ->where('revision', Wiki::find('ops')?->currentRevisionId())
                ->etc());

        $page = Wiki::find('Ops');
        $this->assertSame("On call\nrota", $page?->body);
        $revision = $page->revisions()->first();
        $this->assertSame('Start the ops page', $revision?->summary);
        $this->assertSame((string) $maya->id, (string) $revision->user_id);
        Event::assertDispatched(PageSaved::class);
    }

    public function test_update_needs_the_revision_it_was_based_on(): void
    {
        $this->allowWrites();
        $page = Wiki::create('Ops', 'v1');
        $base = $page->currentRevisionId();

        WikiServer::actingAs($this->user())
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v2', 'summary' => 'Blind write'])
            ->assertHasErrors(["already exists (revision {$base})", 'read_page']);

        $this->assertSame('v1', $page->fresh()?->body);

        WikiServer::tool(WritePage::class, ['title' => 'ops', 'body' => 'v2', 'summary' => 'Update', 'base_revision' => $base])
            ->assertHasNoErrors()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('created', false)
                ->where('changed', true)
                ->where('page.title', 'ops')
                ->etc());

        $this->assertSame('v2', $page->fresh()?->body);
        $this->assertCount(2, $page->revisions);
    }

    public function test_a_stale_base_revision_is_a_conflict_and_saves_nothing(): void
    {
        $this->allowWrites();
        $page = Wiki::create('Ops', 'v1');
        $stale = $page->currentRevisionId();
        $page->commit('Ops', 'v2 from someone else');
        $current = $page->currentRevisionId();

        WikiServer::actingAs($this->user())
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v3', 'summary' => 'Mine', 'base_revision' => $stale])
            ->assertHasErrors(["it is at revision {$current}, not {$stale}", "retry with base_revision {$current}"]);

        $this->assertSame('v2 from someone else', $page->fresh()?->body);
        $this->assertCount(2, $page->revisions);
    }

    public function test_writing_the_same_text_again_records_nothing(): void
    {
        $this->allowWrites();
        $page = Wiki::create('Ops', 'v1');

        WikiServer::actingAs($this->user())
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'Again', 'base_revision' => $page->currentRevisionId()])
            ->assertStructuredContent(fn (AssertableJson $json) => $json->where('changed', false)->where('created', false)->etc());

        $this->assertCount(1, $page->revisions);
    }

    public function test_rename_a_page_by_its_slug(): void
    {
        $this->allowWrites();
        $page = Wiki::create('Ops', 'v1');

        WikiServer::actingAs($this->user())
            ->tool(WritePage::class, ['slug' => 'ops', 'title' => 'Operations', 'body' => 'v1', 'summary' => 'Rename', 'base_revision' => $page->currentRevisionId()])
            ->assertHasNoErrors();

        $this->assertSame('Operations', $page->fresh()?->title);
        $this->assertSame('ops', $page->fresh()?->slug);

        WikiServer::tool(WritePage::class, ['slug' => 'nope', 'title' => 'X', 'body' => '', 'summary' => 'Rename', 'base_revision' => 1])
            ->assertHasErrors(['There is no page with the slug “nope”']);
    }

    public function test_guests_cannot_write(): void
    {
        $this->allowWrites();

        WikiServer::tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'Start'])
            ->assertHasErrors(['needs a signed-in user']);

        $this->assertSame(0, Page::query()->count());
    }

    public function test_writers_need_the_wiki_edit_gate(): void
    {
        $this->allowWrites();
        Gate::define('wiki-edit', fn ($user) => $user->name === 'Maya');

        WikiServer::actingAs($this->user('Tom'))
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'Start'])
            ->assertHasErrors(['permission to edit']);
        $this->assertNull(Wiki::find('Ops'));

        WikiServer::actingAs($this->user('Maya'))
            ->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'Start'])
            ->assertHasNoErrors();
        $this->assertNotNull(Wiki::find('Ops'));
    }

    public function test_writes_are_validated(): void
    {
        $this->allowWrites();
        $server = WikiServer::actingAs($this->user());

        $server->tool(WritePage::class, ['body' => 'v1', 'summary' => 'Start'])->assertHasErrors(['title']);
        $server->tool(WritePage::class, ['title' => 'Ops', 'summary' => 'Start'])->assertHasErrors(['body']);
        $server->tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1'])->assertHasErrors(['summary']);
        $server->tool(WritePage::class, ['title' => '???', 'body' => 'v1', 'summary' => 'Start'])->assertHasErrors(['at least one letter or number']);
    }

    public function test_the_local_server_acts_as_the_configured_user(): void
    {
        $this->allowWrites();
        $maya = $this->user('Maya');
        $this->app['config']->set('wiki.mcp.local_user', $maya->id);

        LocalWikiServer::tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'From my laptop'])
            ->assertHasNoErrors();

        $this->assertSame('Maya', Wiki::find('Ops')?->revisions()->first()?->authorName());
        $server = new LocalWikiServer(new FakeTransporter);
        $server->start();
        $this->assertStringContainsString('write_page', $server->createContext()->instructions);
    }

    public function test_the_local_server_is_a_guest_without_a_configured_user(): void
    {
        $this->allowWrites();
        $this->user('Maya');

        LocalWikiServer::tool(WritePage::class, ['title' => 'Ops', 'body' => 'v1', 'summary' => 'Start'])
            ->assertHasErrors(['needs a signed-in user']);
    }
}
