<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;
use Ruvelo\Wiki\Mcp\WikiServer;

// The MCP endpoint for AI agents. Off by default: set WIKI_MCP=true (or
// wiki.mcp.enabled) and install laravel/mcp. The guard goes on the route
// itself, after laravel/mcp's own middleware, so token failures answer 401.
Route::group(['domain' => config('wiki.domain')], function () {
    Mcp::web(config('wiki.mcp.path', 'mcp/wiki'), WikiServer::class)
        ->name('wiki.mcp')
        ->middleware(config('wiki.mcp.middleware', ['auth:sanctum']));
});
