<?php

namespace Ruvelo\Wiki;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class Wiki
{
    /**
     * Whether the user may create, edit, delete and restore pages.
     *
     * Define a `wiki-edit` gate to decide; without one, any signed-in user can.
     */
    public static function canEdit(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if (Gate::has('wiki-edit')) {
            return Gate::forUser($user)->allows('wiki-edit');
        }

        return $user !== null;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static function userModel(): string
    {
        return config('wiki.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }
}
