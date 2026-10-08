<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Mcp;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Auth;
use Ruvelo\Wiki\Wiki;

/**
 * The wiki's MCP server over stdio, for agents on the same machine:
 * `php artisan mcp:start wiki`. There is no request to sign in from, so it
 * acts as `wiki.mcp.local_user` when set, and as a guest otherwise.
 */
class LocalWikiServer extends WikiServer
{
    protected function boot(): void
    {
        parent::boot();

        $id = config('wiki.mcp.local_user');

        if (($id === null || $id === '') || Auth::user() !== null) {
            return;
        }

        $user = Wiki::userModel()::query()->find($id);

        if ($user instanceof Authenticatable) {
            Auth::setUser($user);
        }
    }
}
