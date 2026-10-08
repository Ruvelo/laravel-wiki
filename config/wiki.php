<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Name
    |--------------------------------------------------------------------------
    |
    | Shown in the header and in page titles.
    |
    */

    'name' => env('WIKI_NAME', 'Wiki'),

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

    'path' => env('WIKI_PATH', 'wiki'),

    'domain' => null,

    'middleware' => ['web'],

    'edit_middleware' => ['auth'],

    /*
    |--------------------------------------------------------------------------
    | JSON API
    |--------------------------------------------------------------------------
    |
    | A REST API for pages and revisions under `prefix`, off by default.
    | Reads need `middleware`; writes also need the `wiki-edit` gate. The
    | default middleware expects Laravel Sanctum; use your own guard if not.
    |
    */

    'api' => [
        'enabled' => (bool) env('WIKI_API', false),
        'prefix' => 'api/wiki',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Markdown
    |--------------------------------------------------------------------------
    |
    | Extra CommonMark extensions (class names or instances), and options
    | merged over the defaults. Raw HTML is escaped unless you change
    | `html_input`; only do that if every editor is trusted.
    |
    */

    'markdown' => [
        'extensions' => [
            // League\CommonMark\Extension\Footnote\FootnoteExtension::class,
        ],
        'options' => [],
    ],

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
    | The wiki creates three tables: {prefix}pages, {prefix}revisions and
    | {prefix}links. Set `run_migrations` to false if you publish the
    | migration and run it yourself.
    |
    */

    'table_prefix' => 'wiki_',

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
