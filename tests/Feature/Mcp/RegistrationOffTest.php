<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature\Mcp;

use Laravel\Mcp\Server\McpServiceProvider;
use Laravel\Mcp\Server\Registrar;
use Ruvelo\Wiki\Tests\TestCase;
use Ruvelo\Wiki\WikiServiceProvider;

class RegistrationOffTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [McpServiceProvider::class, WikiServiceProvider::class];
    }

    public function test_nothing_is_registered_by_default(): void
    {
        $this->assertFalse(app('router')->has('wiki.mcp'));
        $this->assertSame([], $this->app->make(Registrar::class)->servers());
        $this->postJson('/mcp/wiki', [])->assertNotFound();
    }

    public function test_the_wiki_works_without_the_mcp_server(): void
    {
        $this->get('/wiki/_/pages')->assertOk();
    }
}
