# Lore

**A drop-in wiki for Laravel.** Install the package, run the migration, and your app has a wiki at `/wiki`. It has Markdown pages, `[[wiki links]]`, backlinks, full revision history with diffs, one-click restore, and search.

There's no frontend build step, no JavaScript framework and no extra services. It uses the tables in your existing database and your app's own login.

```
composer require francoisbultez/lore
php artisan migrate
```

Then open `/wiki`.

---

## Features

- **Markdown, GitHub-style**: headings, tables, task lists, fenced code, autolinks and strikethrough. Put `[TOC]` on its own line to get a table of contents.
- **Wiki links**: `[[Deploy guide]]` or `[[Deploy guide|how we ship]]`. A link to a page that doesn't exist yet shows in red and opens the create form with the title filled in.
- **Backlinks**: every page lists the pages that link to it ("What links here").
- **Every save is a revision**: browse a page's history, see a line-by-line diff of each change, and restore any old version. A restore is itself a revision, so it can be undone.
- **No lost edits**: if someone saves a page while you're editing it, your save is refused rather than overwriting theirs, and your text stays in the form.
- **Preview before saving.**
- **Search** across titles and content, with snippets. If your search exactly matches a page title, you go straight to that page.
- **Recent changes** across the whole wiki.
- **Safe to render**: raw HTML in the source is escaped and `javascript:` links are dropped. People with edit rights can't inject scripts.
- **Readable URLs in any language**: `Café Crème` → `/wiki/café-crème`, `日本語` → `/wiki/日本語`.
- **Light and dark mode** following the system setting, readable at phone width.

## Requirements

- PHP 8.3+
- Laravel 12 or 13
- Any database Laravel supports (tested on SQLite; plain SQL that also suits MySQL, MariaDB and Postgres)

## Who can edit

By default, **anyone can read and any signed-in user can edit.** To narrow that, define a `lore-edit` gate, for example in your `AppServiceProvider`:

```php
use Illuminate\Support\Facades\Gate;

Gate::define('lore-edit', fn ($user) => $user->is_admin);
```

To make the whole wiki private, wrap every route in `auth` through the config:

```php
'middleware' => ['web', 'auth'],
```

## Configuration

Publish the config file if you want to change the defaults:

```
php artisan vendor:publish --tag=lore-config
```

| Key | Default | |
|---|---|---|
| `name` | `Wiki` (`LORE_NAME`) | Shown in the header and page titles |
| `path` | `wiki` (`LORE_PATH`) | URL prefix |
| `domain` | `null` | Serve the wiki on its own (sub)domain |
| `middleware` | `['web']` | Applied to every route |
| `edit_middleware` | `['auth']` | Added for create/edit/delete/restore |
| `home` | `home` | Slug shown at the wiki root |
| `table_prefix` | `lore_` | Tables are `{prefix}pages`, `{prefix}revisions`, `{prefix}links` |
| `run_migrations` | `true` | Set to `false` if you publish and manage the migration yourself |
| `user_model` | your `users` provider model | Who revisions are attributed to |
| `user_name_attribute` | `name` | Shown as the author in the history |
| `per_page` | `50` | Page size for lists and search |
| `routes` | `true` | Set to `false` to register routes yourself (copy `routes/web.php`) |

## Making it look like your app

The views are plain Blade with the styles inlined in one layout. Publish them and edit as you like:

```
php artisan vendor:publish --tag=lore-views
```

They land in `resources/views/vendor/lore`. Every page extends `layout.blade.php`, so swapping that one file for your own layout is enough to put the wiki inside your app's chrome. Each page fills a `content` section and a `title` section. The layout also has a `lore-head` stack for extra `<head>` tags, and colors are CSS variables (`--lore-accent` and friends) at the top of the layout.

## Using it from code

```php
use FrancoisBultez\Lore\Models\Page;

// Create or update a page; the commit is recorded as a revision.
$page = Page::firstOrNew(['slug' => Page::slugFor('Release notes')]);
$page->commit('Release notes', "## 2.0\n\nSee [[Upgrade guide]].", 'Automated import', $user);

$page->html();               // rendered HTML
$page->revisions;            // newest first
$page->backlinks()->get();   // pages linking here
```

The `FrancoisBultez\Lore\Events\PageSaved` event fires after every create, edit and restore, carrying `$page` and `$revision`. Hook it up for notifications, search indexing or cache busting.

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

## License

MIT. See [LICENSE](LICENSE).
