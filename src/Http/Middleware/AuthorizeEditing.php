<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Ruvelo\Wiki\Wiki;

class AuthorizeEditing
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(Wiki::canEdit($request->user()), 403);

        return $next($request);
    }
}
