<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Ruvelo\Wiki\Tests\TestCase;
use Ruvelo\Wiki\Wiki;

class JsonApiTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('wiki.api.enabled', true);
        $app['config']->set('wiki.api.middleware', ['api']);
    }

    public function test_list_and_search_pages(): void
    {
        Wiki::create('Beta', 'Deploys.');
        Wiki::create('Alpha');

        $this->getJson('/api/wiki/pages')
            ->assertOk()
            ->assertJsonPath('data.*.slug', ['alpha', 'beta'])
            ->assertJsonMissingPath('data.0.body')
            ->assertJsonStructure(['data', 'links', 'meta']);

        $this->getJson('/api/wiki/pages?q=deploy')->assertJsonPath('data.*.slug', ['beta']);
    }

    public function test_show_a_page_in_full(): void
    {
        Wiki::create('Ops');
        $page = Wiki::create('Deploy guide', 'Ask [[Ops]], then [[Nowhere]].');
        Wiki::create('Home', '[[Deploy guide]]');

        $this->getJson('/api/wiki/pages/deploy-guide')
            ->assertOk()
            ->assertJsonPath('data.title', 'Deploy guide')
            ->assertJsonPath('data.revision', $page->currentRevisionId())
            ->assertJsonPath('data.body', 'Ask [[Ops]], then [[Nowhere]].')
            ->assertJsonPath('data.links', ['nowhere', 'ops'])
            ->assertJsonPath('data.backlinks', ['home'])
            ->assertJsonPath('data.url', route('wiki.show', 'deploy-guide'));

        $this->getJson('/api/wiki/pages/missing')->assertNotFound();
    }

    public function test_writes_need_an_editor(): void
    {
        $this->postJson('/api/wiki/pages', ['title' => 'Ops'])->assertUnauthorized();

        Gate::define('wiki-edit', fn ($user) => false);
        $this->actingAs($this->user())->postJson('/api/wiki/pages', ['title' => 'Ops'])->assertForbidden();
    }

    public function test_create_a_page(): void
    {
        $this->actingAs($this->user())
            ->postJson('/api/wiki/pages', ['title' => 'Ops', 'body' => "Line\r\nbreak"])
            ->assertCreated()
            ->assertJsonPath('data.slug', 'ops')
            ->assertJsonPath('data.body', "Line\nbreak");

        $this->postJson('/api/wiki/pages', ['title' => 'ops'])
            ->assertStatus(409)
            ->assertJsonPath('page', route('wiki.api.pages.show', 'ops'));

        $this->postJson('/api/wiki/pages', ['title' => '???'])->assertUnprocessable()->assertJsonValidationErrors('title');
    }

    public function test_update_with_conflict_detection(): void
    {
        $page = Wiki::create('Ops', 'v1');
        $base = $page->currentRevisionId();
        $this->actingAs($this->user());

        $this->patchJson('/api/wiki/pages/ops', ['body' => 'v2', 'base_revision' => $base])
            ->assertOk()
            ->assertJsonPath('data.body', 'v2');

        $this->patchJson('/api/wiki/pages/ops', ['body' => 'v3', 'base_revision' => $base])
            ->assertStatus(409)
            ->assertJsonPath('current_revision', $page->currentRevisionId());

        $this->patchJson('/api/wiki/pages/ops', ['title' => 'Operations'])
            ->assertOk()
            ->assertJsonPath('data.title', 'Operations')
            ->assertJsonPath('data.body', 'v2')
            ->assertJsonPath('data.slug', 'ops');
    }

    public function test_delete_a_page(): void
    {
        Wiki::create('Ops');

        $this->actingAs($this->user())->deleteJson('/api/wiki/pages/ops')->assertNoContent();

        $this->assertNull(Wiki::find('Ops'));
    }

    public function test_revisions_and_restore(): void
    {
        $page = Wiki::create('Ops', 'v1');
        $first = $page->currentRevisionId();
        $page->commit('Ops', 'v2', 'Second');

        $this->getJson('/api/wiki/pages/ops/revisions')
            ->assertOk()
            ->assertJsonPath('data.*.summary', ['Second', 'Created page'])
            ->assertJsonMissingPath('data.0.body');

        $this->getJson("/api/wiki/pages/ops/revisions/{$first}")->assertJsonPath('data.body', 'v1');

        $this->actingAs($this->user())
            ->postJson("/api/wiki/pages/ops/revisions/{$first}/restore")
            ->assertOk()
            ->assertJsonPath('data.body', 'v1');
    }
}
