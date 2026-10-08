<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Tests\Feature\Mcp;

use Laravel\Mcp\Server\McpServiceProvider;
use Ruvelo\Wiki\Tests\TestCase;
use Ruvelo\Wiki\WikiServiceProvider;

abstract class McpTestCase extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [McpServiceProvider::class, WikiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('wiki.mcp.enabled', true);
        $app['config']->set('wiki.mcp.middleware', ['auth']);
    }
}
