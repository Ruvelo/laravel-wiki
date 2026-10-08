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
    | and restoring. Editing always requires a signed-in user who passes the
    | `wiki-edit` gate; guests go to your `login` route if you have one.
    | Set `routes` to false to register your own.
    |
    */

    'routes' => true,

    'path' => env('WIKI_PATH', 'wiki'),

    'domain' => null,

    'middleware' => ['web'],

    'edit_middleware' => [],

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
    | MCP server (AI agents)
    |--------------------------------------------------------------------------
    |
    | Lets AI agents (Claude Code, Cursor, ChatGPT...) search and read the
    | wiki over the Model Context Protocol. Needs `composer require
    | laravel/mcp`; off by default.
    |
    | `path` is the HTTP endpoint, guarded by `middleware` (a token guard,
    | since agents can't hold a session); null turns it off. `local` is the
    | handle for `php artisan mcp:start {local}`, for agents on the same
    | machine; null turns it off. A local server has no signed-in user, so
    | set `local_user` (a user id) if it should write as someone.
    |
    | Writing is off unless `allow_writes` is true, and then still needs the
    | `wiki-edit` gate, like the web UI.
    |
    */

    'mcp' => [
        'enabled' => (bool) env('WIKI_MCP', false),
        'path' => 'mcp/wiki',
        'middleware' => ['auth:sanctum'],
        'local' => 'wiki',
        'local_user' => env('WIKI_MCP_USER'),
        'allow_writes' => (bool) env('WIKI_MCP_WRITES', false),
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
    | Menu
    |--------------------------------------------------------------------------
    |
    | The left-hand menu is the page with this slug: headings become groups
    | and [[links]] become items. Without that page, the menu lists pages
    | alphabetically. Set to null to always use the automatic list.
    |
    */

    'sidebar' => 'sidebar',

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
