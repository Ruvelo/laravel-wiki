<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Name
    |--------------------------------------------------------------------------
    |
    | Shown in the header and in page titles.
    |
    */

    'name' => env('LORE_NAME', 'Wiki'),

    /*
    |--------------------------------------------------------------------------
    | Routing
    |--------------------------------------------------------------------------
    |
    | The wiki is served under `path` (e.g. /wiki). `middleware` wraps every
    | route; `edit_middleware` is added on top for creating, editing, deleting
    | and restoring. Set `routes` to false to register your own.
    |
    */

    'routes' => true,

    'path' => env('LORE_PATH', 'wiki'),

    'domain' => null,

    'middleware' => ['web'],

    'edit_middleware' => ['auth'],

    /*
    |--------------------------------------------------------------------------
    | Home page
    |--------------------------------------------------------------------------
    |
    | The slug shown at the wiki root.
    |
    */

    'home' => 'home',

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    |
    | Lore creates three tables: {prefix}pages, {prefix}revisions and
    | {prefix}links. Set `run_migrations` to false if you publish the
    | migration and run it yourself.
    |
    */

    'table_prefix' => 'lore_',

    'run_migrations' => true,

    /*
    |--------------------------------------------------------------------------
    | Authors
    |--------------------------------------------------------------------------
    |
    | The model revisions are attributed to, and the attribute used as the
    | author's display name. Defaults to your `users` auth provider's model.
    |
    */

    'user_model' => null,

    'user_name_attribute' => 'name',

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    'per_page' => 50,

];
