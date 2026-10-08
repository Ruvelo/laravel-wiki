<?php

namespace FrancoisBultez\Lore;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;

final class Lore
{
    /**
     * Whether the user may create, edit, delete and restore pages.
     *
     * Define a `lore-edit` gate to decide; without one, any signed-in user can.
     */
    public static function canEdit(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if (Gate::has('lore-edit')) {
            return Gate::forUser($user)->allows('lore-edit');
        }

        return $user !== null;
    }

    /**
     * @return class-string<\Illuminate\Database\Eloquent\Model>
     */
    public static function userModel(): string
    {
        return config('lore.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }
}
