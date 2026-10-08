<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Mcp;

use Composer\InstalledVersions;
use Laravel\Mcp\Server;
use Ruvelo\Wiki\Mcp\Resources\WikiPage;
use Ruvelo\Wiki\Mcp\Tools\ListPages;
use Ruvelo\Wiki\Mcp\Tools\ReadPage;
use Ruvelo\Wiki\Mcp\Tools\RecentChanges;
use Ruvelo\Wiki\Mcp\Tools\SearchPages;
use Ruvelo\Wiki\Mcp\Tools\WritePage;

/**
 * The wiki as an MCP server: AI agents search and read it as a knowledge
 * source, and write to it when `wiki.mcp.allow_writes` is on.
 *
 * Registered for you when `wiki.mcp.enabled` is true. To mount it yourself,
 * in routes/ai.php: `Mcp::web('/mcp/wiki', WikiServer::class)->middleware('auth:sanctum');`
 */
class WikiServer extends Server
{
    protected array $tools = [
        SearchPages::class,
        ReadPage::class,
        ListPages::class,
        RecentChanges::class,
        WritePage::class,
    ];

    protected array $resources = [
        WikiPage::class,
    ];

    protected function boot(): void
    {
        $this->name = (string) config('wiki.name', 'Wiki');
        $this->version = InstalledVersions::getPrettyVersion('ruvelo/laravel-wiki') ?? $this->version;
        $this->addCapability(self::CAPABILITY_COMPLETIONS);

        $this->instructions = 'This is the team wiki: the place where how things work is written down. '
            .'Search it before answering questions about the product, processes or decisions, and cite the page URL. '
            .'Use search_pages to find pages, read_page to read one in full, list_pages to browse, and recent_changes to see what changed lately. '
            .'Pages are Markdown; [[Page title]] links to another page.'
            .(config('wiki.mcp.allow_writes', false)
                ? ' You may also edit with write_page: read the page first, send its whole new text with the revision you read as base_revision, and give a short summary. Never write without being asked to.'
                : ' The wiki is read-only here.');
    }
}
