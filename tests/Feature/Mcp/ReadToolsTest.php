<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature\Mcp;

use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use Ruvelo\Wiki\Mcp\Resources\WikiPage;
use Ruvelo\Wiki\Mcp\Tools\ListPages;
use Ruvelo\Wiki\Mcp\Tools\ReadPage;
use Ruvelo\Wiki\Mcp\Tools\RecentChanges;
use Ruvelo\Wiki\Mcp\Tools\SearchPages;
use Ruvelo\Wiki\Mcp\Tools\WritePage;
use Ruvelo\Wiki\Mcp\WikiServer;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Wiki;

class ReadToolsTest extends McpTestCase
{
    public function test_read_tools_are_offered_and_writing_is_not_by_default(): void
    {
        WikiServer::tools()
            ->assertRegistered([SearchPages::class, ReadPage::class, ListPages::class, RecentChanges::class])
            ->assertNotRegistered(WritePage::class);
    }

    public function test_search_pages_returns_titles_slugs_snippets_and_urls(): void
    {
        Wiki::create('Refunds', 'Customers get a refund within 30 days.');
        Wiki::create('Billing', 'Invoices go out monthly. See [[Refunds]] for the refund policy, which is short.');
        Wiki::create('Deploys', 'Nothing about money here.');

        WikiServer::tool(SearchPages::class, ['query' => 'refund'])
            ->assertOk()
            ->assertHasNoErrors()
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('query', 'refund')
                ->has('results', 2)
                ->has('results.0', fn (AssertableJson $result) => $result
                    ->where('title', 'Refunds')
                    ->where('slug', 'refunds')
                    ->where('url', route('wiki.show', 'refunds'))
                    ->where('uri', 'wiki://pages/refunds')
                    ->where('snippet', 'Customers get a refund within 30 days.')
                    ->has('updated_at'))
                ->where('results.1.slug', 'billing')
                ->where('results.1.snippet', fn (string $snippet) => str_contains($snippet, 'Refunds for the refund policy'))
            );
    }

    public function test_search_pages_needs_a_query_and_caps_the_limit(): void
    {
        foreach (range(1, 3) as $n) {
            Wiki::create("Runbook {$n}");
        }

        WikiServer::tool(SearchPages::class, ['query' => ''])->assertHasErrors(['query']);
        WikiServer::tool(SearchPages::class, ['query' => 'runbook', 'limit' => 500])->assertHasErrors(['limit']);
        WikiServer::tool(SearchPages::class, ['query' => 'runbook', 'limit' => 2])
            ->assertStructuredContent(fn (AssertableJson $json) => $json->has('results', 2)->etc());
    }

    public function test_read_page_by_slug_or_title_with_links_and_backlinks(): void
    {
        Wiki::create('Ops');
        $page = Wiki::create('Deploy guide', "# Deploys\n\nAsk [[Ops]], then [[Nowhere]].");
        Wiki::create('Home', 'Start with the [[Deploy guide]].');

        foreach (['deploy-guide', 'Deploy guide'] as $reference) {
            WikiServer::tool(ReadPage::class, ['page' => $reference])
                ->assertHasNoErrors()
                ->assertStructuredContent([
                    'title' => 'Deploy guide',
                    'slug' => 'deploy-guide',
                    'url' => route('wiki.show', 'deploy-guide'),
                    'uri' => 'wiki://pages/deploy-guide',
                    'updated_at' => $page->updated_at->toIso8601String(),
                    'revision' => $page->currentRevisionId(),
                    'body' => "# Deploys\n\nAsk [[Ops]], then [[Nowhere]].",
                    'links' => [
                        ['slug' => 'nowhere', 'title' => null, 'exists' => false],
                        ['slug' => 'ops', 'title' => 'Ops', 'exists' => true],
                    ],
                    'backlinks' => [['title' => 'Home', 'slug' => 'home']],
                ]);
        }
    }

    public function test_read_page_explains_a_missing_page(): void
    {
        WikiServer::tool(ReadPage::class, ['page' => 'Nope'])->assertHasErrors(['There is no page called “Nope”']);
        WikiServer::tool(ReadPage::class, [])->assertHasErrors(['page']);
    }

    public function test_list_pages_is_alphabetical_and_paginated(): void
    {
        foreach (['Cedar', 'Alder', 'Birch'] as $title) {
            Wiki::create($title);
        }

        WikiServer::tool(ListPages::class, ['per_page' => 2])
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->where('pages.0.title', 'Alder')
                ->where('pages.1.title', 'Birch')
                ->has('pages', 2)
                ->where('page', 1)
                ->where('per_page', 2)
                ->where('total', 3)
                ->where('has_more', true));

        WikiServer::tool(ListPages::class, ['per_page' => 2, 'page' => 2])
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('pages', 1)
                ->where('pages.0.slug', 'cedar')
                ->where('has_more', false)
                ->etc());

        WikiServer::tool(ListPages::class, ['per_page' => 101])->assertHasErrors(['per page']);
    }

    public function test_recent_changes_lists_edits_with_summaries_newest_first(): void
    {
        $maya = $this->user('Maya');
        $ops = Wiki::create('Ops', 'v1', 'Created page', $maya);
        Wiki::create('Billing');
        $ops->commit('Ops', 'v2', 'Add the on-call rota', $maya);

        WikiServer::tool(RecentChanges::class)
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('changes', 3)
                ->has('changes.0', fn (AssertableJson $change) => $change
                    ->where('revision', $ops->currentRevisionId())
                    ->where('title', 'Ops')
                    ->where('slug', 'ops')
                    ->where('url', route('wiki.show', 'ops'))
                    ->where('summary', 'Add the on-call rota')
                    ->where('author', 'Maya')
                    ->has('created_at'))
                ->where('changes.1.slug', 'billing')
                ->where('changes.1.author', null));

        WikiServer::tool(RecentChanges::class, ['page' => 'Ops', 'limit' => 1])
            ->assertStructuredContent(fn (AssertableJson $json) => $json
                ->has('changes', 1)
                ->where('changes.0.summary', 'Add the on-call rota'));

        WikiServer::tool(RecentChanges::class, ['page' => 'Nope'])->assertHasErrors(['There is no page called “Nope”']);
    }

    public function test_each_page_is_a_resource(): void
    {
        Wiki::create('Deploy guide', 'Ship on Tuesdays.');

        WikiServer::resource(WikiPage::class, ['slug' => 'deploy-guide'])
            ->assertOk()
            ->assertSee("# Deploy guide\n\nShip on Tuesdays.");

        WikiServer::resource(WikiPage::class, ['slug' => 'nope'])->assertHasErrors(['There is no page with the slug “nope”']);
    }

    public function test_page_resources_decode_percent_encoded_slugs(): void
    {
        Wiki::create('Café crème', 'Hot.');

        WikiServer::resource(WikiPage::class, ['slug' => rawurlencode(Page::slugFor('Café crème'))])->assertSee('Hot.');
    }

    public function test_page_slugs_complete_as_you_type(): void
    {
        Wiki::create('Deploy guide');
        Wiki::create('Deploy checklist');
        Wiki::create('Billing');

        WikiServer::completion(WikiPage::class, 'slug', 'deploy')
            ->assertCompletionValues(['deploy-checklist', 'deploy-guide']);
    }

    public function test_the_server_describes_itself(): void
    {
        $this->app['config']->set('wiki.name', 'Halyard handbook');

        $server = new WikiServer(new FakeTransporter);
        $server->start();

        $this->assertSame('Halyard handbook', $server->createContext()->implementation->name);
        $this->assertStringContainsString('read-only', $server->createContext()->instructions);
    }
}
