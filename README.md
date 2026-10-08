<p align="center">
  <a href="https://ruvelo.github.io/laravel-wiki/"><img src="art/banner.png" alt="Laravel Wiki: the wiki that lives inside your Laravel app" width="100%"></a>
</p>

<p align="center">
  <a href="https://ruvelo.github.io/laravel-wiki/demo/"><strong>Live demo</strong></a> &nbsp;&nbsp;&nbsp;
  <a href="https://ruvelo.github.io/laravel-wiki/docs.html"><strong>Docs</strong></a> &nbsp;&nbsp;&nbsp;
  <a href="https://ruvelo.github.io/laravel-wiki/"><strong>Website</strong></a>
</p>

<p align="center">
  <a href="https://github.com/Ruvelo/laravel-wiki/actions/workflows/tests.yml"><img src="https://github.com/Ruvelo/laravel-wiki/actions/workflows/tests.yml/badge.svg" alt="Tests"></a>
  <img src="https://img.shields.io/badge/Laravel-12%20%7C%2013-2251d1" alt="Laravel 12 | 13">
  <img src="https://img.shields.io/badge/PHP-8.3%2B-2251d1" alt="PHP 8.3+">
  <a href="LICENSE"><img src="https://img.shields.io/badge/license-MIT-2251d1" alt="MIT license"></a>
</p>

# Laravel Wiki

**A drop-in wiki for your Laravel app.** Markdown pages, `[[links]]` between them, and the full history of every change. It runs on your database and your logins, with no frontend build step and no extra services.

```
composer require ruvelo/laravel-wiki
php artisan migrate
```

Then open `/wiki`. Or click around the [live demo](https://ruvelo.github.io/laravel-wiki/demo/) first.

## A quick tour

<img src="art/screenshot-page.png" alt="A wiki page with a table of contents, blue links to existing pages and a red link to a page not written yet">

**Pages link to each other.** Write `[[Deploy guide]]` and it becomes a link. Pages that don't exist yet show in red, an open invitation to whoever knows the answer.

<img src="art/screenshot-editor.png" alt="The editor: Markdown on the left, live preview on the right">

**Edit with a live preview.** Markdown on the left, the finished page on the right, updated as you type.

<img src="art/screenshot-diff.png" alt="A revision with added lines in green and removed lines in red">

**Every save is kept.** See what changed, who changed it, and put any old version back in one click.

<table>
  <tr>
    <td width="50%"><img src="art/screenshot-dark.png" alt="Dark mode"></td>
    <td width="50%"><img src="art/screenshot-search.png" alt="Search results with highlighted matches"></td>
  </tr>
  <tr>
    <td><strong>Light and dark</strong>, following each reader's system setting.</td>
    <td><strong>Search</strong> with highlighted snippets; an exact title takes you straight to the page.</td>
  </tr>
</table>

## Features

- **Markdown, GitHub-style**: headings, tables, task lists, fenced code, autolinks and strikethrough. Put `[TOC]` on its own line to get a table of contents.
- **Wiki links**: `[[Deploy guide]]` or `[[Deploy guide|how we ship]]`. A link to a page that doesn't exist yet shows in red; for editors it opens the create form with the title filled in.
- **What links here**: every page lists the pages that link to it.
- **Every save is a revision**: browse a page's history, see a line-by-line diff of each change, and restore any old version. A restore is itself a revision, so it can be undone.
- **No lost edits**: if someone saves a page while you're editing it, your save is refused rather than overwriting theirs, and your text stays in the editor.
- **Live preview** beside the editor, updated as you type.
- **Search** across titles and content, with highlighted snippets.
- **Recent changes** across the whole wiki.
- **Safe to render**: raw HTML in the source is escaped and `javascript:` links are dropped. People with edit rights can't inject scripts.
- **Readable URLs in any language**: `Café Crème` → `/wiki/café-crème`, `日本語` → `/wiki/日本語`.
- **Light and dark mode** following the system setting, readable at phone width.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Any database Laravel supports (tested on SQLite; plain SQL that also suits MySQL, MariaDB and Postgres)

## Who can edit

By default, **anyone can read and any signed-in user can edit.** To narrow that, define a `wiki-edit` gate, for example in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('wiki-edit', fn ($user) => $user->is_admin);
```

To make the whole wiki private, wrap every route in `auth` through the config:

```php
'middleware' => ['web', 'auth'],
```

## Configuration

Publish the config file if you want to change the defaults:

```
php artisan vendor:publish --tag=wiki-config
```

| Key | Default | |
|---|---|---|
| `name` | `Wiki` (`WIKI_NAME`) | Shown in the header and page titles |
| `path` | `wiki` (`WIKI_PATH`) | URL prefix |
| `domain` | `null` | Serve the wiki on its own (sub)domain |
| `middleware` | `['web']` | Applied to every route |
| `edit_middleware` | `['auth']` | Added for create/edit/delete/restore |
| `home` | `home` | Slug shown at the wiki root |
| `table_prefix` | `wiki_` | Tables are `{prefix}pages`, `{prefix}revisions`, `{prefix}links` |
| `run_migrations` | `true` | Set to `false` if you publish and manage the migration yourself |
| `user_model` | your `users` provider model | Who revisions are attributed to |
| `user_name_attribute` | `name` | Shown as the author in the history |
| `per_page` | `50` | Page size for lists and search |
| `routes` | `true` | Set to `false` to register routes yourself (copy `routes/web.php`) |

## Making it look like your app

The views are plain Blade with the styles inlined in one layout. Publish them and edit as you like:

```
php artisan vendor:publish --tag=wiki-views
```

They land in `resources/views/vendor/wiki`. Every page extends `layout.blade.php`, so swapping that one file for your own layout is enough to put the wiki inside your app's chrome. Each page fills a `content` section and a `title` section. The layout also has a `wiki-head` stack for extra `<head>` tags, and colors are CSS variables (`--wiki-accent` and friends) at the top of the layout.

## Using it from code

```php
use Ruvelo\Wiki\Models\Page;

// Create or update a page; the commit is recorded as a revision.
$page = Page::firstOrNew(['slug' => Page::slugFor('Release notes')]);
$page->commit('Release notes', "## 2.0\n\nSee [[Upgrade guide]].", 'Automated import', $user);

$page->html();               // rendered HTML
$page->revisions;            // newest first
$page->backlinks()->get();   // pages linking here
```

The `Ruvelo\Wiki\Events\PageSaved` event fires after every create, edit and restore, carrying `$page` and `$revision`. Hook it up for notifications, search indexing or cache busting.

## URLs

| | |
|---|---|
| `/wiki` | Home page |
| `/wiki/{slug}` | A page |
| `/wiki/{slug}/edit` | Edit |
| `/wiki/{slug}/history` | Revisions |
| `/wiki/{slug}/history/{id}` | One revision and its diff |
| `/wiki/_/new` | Create (`?title=` to pre-fill) |
| `/wiki/_/pages` | All pages |
| `/wiki/_/recent` | Recent changes |
| `/wiki/_/search?q=` | Search |

Special pages live under `/_/`, which can never collide with a page: slugs never contain an underscore.

Renaming a page keeps its address. Links written with the old title (`[[Old title]]`) still resolve, because they point at the slug.

## Testing

```
composer install
composer test
```

The demo and the screenshots are built from the package itself: `php demo/build.php build/site` writes the static demo, and `demo/screenshots.sh` regenerates the images in `art/`. The website deploys from `site/` to GitHub Pages on every push to `main`.

## Credits

Built by [François Bultez](https://github.com/francoisbultez) at [Ruvelo](https://github.com/ruvelo).

## License

MIT. See [LICENSE](LICENSE).
