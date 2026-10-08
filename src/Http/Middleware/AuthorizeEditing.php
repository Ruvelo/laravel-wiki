<?php

namespace Ruvelo\Wiki\Http\Middleware;

use Closure;
use Ruvelo\Wiki\Wiki;
use Illuminate\Http\Request;

class AuthorizeEditing
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(Wiki::canEdit($request->user()), 403);

        return $next($request);
    }
}
