<?php

namespace FrancoisBultez\Lore\Http\Middleware;

use Closure;
use FrancoisBultez\Lore\Lore;
use Illuminate\Http\Request;

class AuthorizeEditing
{
    public function handle(Request $request, Closure $next): mixed
    {
        abort_unless(Lore::canEdit($request->user()), 403);

        return $next($request);
    }
}
