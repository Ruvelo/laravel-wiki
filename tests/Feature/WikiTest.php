<?php

namespace Ruvelo\Wiki\Tests\Feature;

use Ruvelo\Wiki\Events\PageSaved;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Tests\TestCase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class WikiTest extends TestCase
{
    private function page(string $title, string $body = ''): Page
    {
        $page = new Page(['slug' => Page::slugFor($title)]);
        $page->commit($title, $body, 'Created page');

        return $page;
    }

    public function test_the_root_shows_the_home_page(): void
    {
        $this->page('Home', 'Welcome to **the** wiki.');

        $this->get('/wiki')->assertOk()->assertSee('<strong>the</strong>', false);
    }

    public function test_a_missing_page_offers_to_create_it_to_editors_only(): void
    {
        $this->get('/wiki/nothing-here')->assertNotFound()->assertSee('no page here yet')->assertDontSee('Create “');

        $this->actingAs($this->user())
            ->get('/wiki/nothing-here')->assertNotFound()->assertSee('Create “Nothing here”', false);
    }

    public function test_guests_cannot_edit(): void
    {
        $page = $this->page('Home');

        $this->getJson('/wiki/_/new')->assertUnauthorized();
        $this->postJson('/wiki/_/new', ['title' => 'x'])->assertUnauthorized();
        $this->putJson("/wiki/{$page->slug}", ['title' => 'x'])->assertUnauthorized();
        $this->deleteJson("/wiki/{$page->slug}")->assertUnauthorized();
    }

    public function test_a_gate_can_restrict_editing(): void
    {
        Gate::define('wiki-edit', fn ($user) => $user->name === 'Editor');

        $this->actingAs($this->user('Reader'))->get('/wiki/_/new')->assertForbidden();
        $this->actingAs($this->user('Editor'))->get('/wiki/_/new')->assertOk();
    }

    public function test_creating_a_page_records_a_revision_and_its_links(): void
    {
        Event::fake([PageSaved::class]);
        $user = $this->user();

        $this->actingAs($user)
            ->post('/wiki/_/new', ['title' => 'Deploy guide', 'body' => "Ask [[Ops]] first.\r\nThen [[Ops|them]] again."])
            ->assertRedirect('/wiki/deploy-guide');

        $page = Page::where('slug', 'deploy-guide')->firstOrFail();
        $this->assertSame("Ask [[Ops]] first.\nThen [[Ops|them]] again.", $page->body);
        $this->assertSame('Created page', $page->revisions()->first()->summary);
        $this->assertSame((string) $user->id, $page->revisions()->first()->user_id);
        $this->assertSame(['ops'], $this->linkTargets($page));

        Event::assertDispatched(PageSaved::class, fn ($event) => $event->page->is($page));
    }

    public function test_titles_must_be_unique_by_slug(): void
    {
        $this->page('Deploy guide');

        $this->actingAs($this->user())
            ->post('/wiki/_/new', ['title' => 'deploy  GUIDE'])
            ->assertSessionHasErrors('title');
    }

    public function test_backlinks_list_pages_that_link_here(): void
    {
        $ops = $this->page('Ops');
        $this->page('Deploy guide', 'Ask [[Ops]].');
        $this->page('Unrelated');

        $this->get('/wiki/ops')->assertSee('What links here')->assertSee('Deploy guide')->assertDontSee('Unrelated');
        $this->assertSame(['Deploy guide'], $ops->backlinks()->pluck('title')->all());
    }

    public function test_editing_creates_a_new_revision_and_keeps_the_slug(): void
    {
        $page = $this->page('Home', 'v1');
        $base = $page->revisions()->value('id');

        $this->actingAs($this->user())
            ->put('/wiki/home', ['title' => 'Start here', 'body' => 'v2', 'summary' => 'Renamed', 'base' => $base])
            ->assertRedirect('/wiki/home');

        $page->refresh();
        $this->assertSame(['Start here', 'v2', 'home'], [$page->title, $page->body, $page->slug]);
        $this->assertSame(2, $page->revisions()->count());
    }

    public function test_saving_without_changes_adds_no_revision(): void
    {
        $page = $this->page('Home', 'same');

        $this->actingAs($this->user())
            ->put('/wiki/home', ['title' => 'Home', 'body' => 'same', 'base' => $page->revisions()->value('id')])
            ->assertSessionHas('wiki.status', 'No changes to save.');

        $this->assertSame(1, $page->revisions()->count());
    }

    public function test_a_stale_edit_is_rejected_instead_of_overwriting(): void
    {
        $page = $this->page('Home', 'v1');
        $staleBase = $page->revisions()->value('id');
        $page->commit('Home', 'someone else’s v2');

        $this->actingAs($this->user())
            ->put('/wiki/home', ['title' => 'Home', 'body' => 'my v2', 'base' => $staleBase])
            ->assertSessionHasErrors('body');

        $this->assertSame('someone else’s v2', $page->fresh()->body);
    }

    public function test_preview_renders_without_saving(): void
    {
        $this->actingAs($this->user())
            ->post('/wiki/_/new', ['title' => 'Draft', 'body' => '# Hello', 'action' => 'preview'])
            ->assertOk()
            ->assertSee('Preview, not saved yet');

        $this->assertFalse(Page::where('slug', 'draft')->exists());
    }

    public function test_history_diff_and_restore(): void
    {
        $page = $this->page('Home', "line one\nline two");
        $first = $page->revisions()->first();
        $second = $page->commit('Home', "line one\nline 2", 'Tweak');

        $this->get('/wiki/home/history')->assertOk()->assertSee('Tweak')->assertSee('#'.$first->id);

        $this->get("/wiki/home/history/{$second->id}")
            ->assertOk()
            ->assertSee('- line two')
            ->assertSee('+ line 2');

        $this->actingAs($this->user())
            ->post("/wiki/home/history/{$first->id}/restore")
            ->assertRedirect('/wiki/home');

        $this->assertSame("line one\nline two", $page->fresh()->body);
        $this->assertSame(3, $page->revisions()->count());
    }

    public function test_revisions_are_scoped_to_their_page(): void
    {
        $this->page('Home');
        $other = $this->page('Other');

        $this->get('/wiki/home/history/'.$other->revisions()->value('id'))->assertNotFound();
    }

    public function test_deleting_a_page_removes_its_history_and_links(): void
    {
        $page = $this->page('Home', '[[Ops]]');

        $this->actingAs($this->user())->delete('/wiki/home')->assertRedirect('/wiki/_/pages');

        $this->assertFalse(Page::whereKey($page->id)->exists());
        $this->assertDatabaseCount('wiki_revisions', 0);
        $this->assertDatabaseCount('wiki_links', 0);
    }

    public function test_search_matches_titles_and_bodies(): void
    {
        $this->page('Deploy guide', 'We use cron to pull.');
        $this->page('Cron jobs', 'Every minute.');
        $this->page('Unrelated', 'Nothing.');

        $this->get('/wiki/_/search?q=cron')
            ->assertOk()
            ->assertSeeInOrder(['Cron jobs', 'Deploy guide'])
            ->assertDontSee('Unrelated');
    }

    public function test_search_jumps_to_an_exact_title(): void
    {
        $this->page('Cron jobs');

        $this->get('/wiki/_/search?q=Cron+Jobs')->assertRedirect('/wiki/cron-jobs');
        $this->get('/wiki/_/search?q=Cron+Jobs&all=1')->assertOk();
    }

    public function test_search_treats_wildcards_literally(): void
    {
        $this->page('Discounts', 'Save 50% today.');
        $this->page('Other', 'Save 50 dollars.');

        $this->get('/wiki/_/search?q=50%25')->assertSee('Discounts')->assertDontSee('Other');
    }

    public function test_index_and_recent_changes_list_pages(): void
    {
        $this->page('Beta');
        $this->page('Alpha');

        $this->get('/wiki/_/pages')->assertOk()->assertSeeInOrder(['Alpha', 'Beta']);
        $this->get('/wiki/_/recent')->assertOk()->assertSeeInOrder(['Alpha', 'Beta']);
    }

    private function linkTargets(Page $page): array
    {
        return $page->getConnection()->table(Page::linksTable())
            ->where('page_id', $page->id)->orderBy('target_slug')->pluck('target_slug')->all();
    }
}
