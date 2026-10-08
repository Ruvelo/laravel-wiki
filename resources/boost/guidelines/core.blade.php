## ruvelo/laravel-wiki

A drop-in wiki: Markdown pages with `[[wiki links]]`, backlinks, a revision for every save, and search. Pages live in `{prefix}pages`, `{prefix}revisions` and `{prefix}links` (prefix `wiki_` by default), served under `/wiki`.

### Reading and writing pages

Use the static `Ruvelo\Wiki\Wiki` API. Every change goes through `Page::commit()` (which `Wiki::write()` and `Wiki::create()` call): it saves the page, records the revision, re-indexes links and fires `PageSaved`, in one transaction.

@verbatim
<code-snippet name="Write and read pages" lang="php">
use Ruvelo\Wiki\Wiki;

$page = Wiki::write('Release notes', $markdown, 'v2.0 notes', $user); // create or update; identical text records nothing
Wiki::create('Onboarding', $markdown, 'Created page', $user);          // throws PageAlreadyExists if the title is taken
$page = Wiki::find('Release notes');                                   // by title or slug, null if missing
Wiki::search('deploy')->limit(5)->get();                               // title matches first
$page->html();                                                         // safe HTML, links resolved
$page->backlinks()->get();                                             // pages linking here
$page->outgoingLinks();                                                // slugs this page links to
</code-snippet>
@endverbatim

- To refuse a save when someone else saved first, pass the revision the edit started from: `Wiki::write($title, $body, $summary, $user, basedOn: $revisionId)` or `$page->commit($title, $body, $summary, $user, basedOn: $revisionId)`. It throws `EditConflict` (with `currentRevision`) instead of overwriting. Get the current one with `$page->currentRevisionId()`.
- Exceptions all extend `Ruvelo\Wiki\Exceptions\WikiException`: `EditConflict`, `PageAlreadyExists` (has `->page`), `InvalidTitle` (a title needs a letter or number).
- Slugs come from `Page::slugFor($title)`; never build them by hand. Renaming keeps the slug.

### Events

Listen to `Ruvelo\Wiki\Events\PageSaved` (`->page`, `->revision`, `->wasCreated()`) and `PageDeleted` (`->page`) rather than model events or observers on the wiki tables.

### Do not

- Do not insert or update `wiki_pages`, `wiki_revisions` or `wiki_links` directly, or call `Page::create()`/`$page->update()`: history, the link index and events would be skipped. Always go through `Wiki::write()`, `Wiki::create()` or `$page->commit()`.
- Do not render page Markdown yourself, or with `html_input` other than `escape` or with `allow_unsafe_links`: use `$page->html()` or `Wiki::render($markdown)`, which escape raw HTML and drop `javascript:` links. Add CommonMark extensions through `config('wiki.markdown.extensions')` or `Wiki::extendMarkdown(fn (Environment $env) => ...)` in a service provider.
- Do not check edit rights with your own logic: use `Wiki::canEdit($user)`, and change who can edit by defining the `wiki-edit` gate.

@verbatim
<code-snippet name="Who can edit" lang="php">
// AppServiceProvider::boot(). Without a gate, any signed-in user can edit.
Gate::define('wiki-edit', fn (User $user) => $user->is_admin);
</code-snippet>
@endverbatim

### The menu

The left-hand menu is an ordinary page, slug `sidebar` (config `wiki.sidebar`): `##` headings become groups and `[[links]]` become items. Edit it with `Wiki::write('Sidebar', ...)` like any page.

### JSON API

Off by default (`WIKI_API=true`). Under `/api/wiki` with `['api', 'auth:sanctum']`: `GET pages?q=`, `GET pages/{slug}` (with `body`, `html`, `revision`, `links`, `backlinks`), `POST pages`, `PATCH pages/{slug}` (send `base_revision`; a newer save answers `409`), `DELETE pages/{slug}`, `GET pages/{slug}/revisions`, `POST pages/{slug}/revisions/{id}/restore`. Writes also need the `wiki-edit` gate.

### MCP server for AI agents

With `laravel/mcp` installed and `WIKI_MCP=true`, agents get `search_pages`, `read_page`, `list_pages`, `recent_changes` and the `wiki://pages/{slug}` resource at `/mcp/wiki` (middleware `wiki.mcp.middleware`, `auth:sanctum` by default) or locally via `php artisan mcp:start wiki`. `write_page` exists only with `wiki.mcp.allow_writes` (`WIKI_MCP_WRITES=true`) and the `wiki-edit` gate.

### Config (`config/wiki.php`)

`path`, `domain`, `middleware`, `edit_middleware`, `home`, `sidebar`, `table_prefix`, `run_migrations`, `user_model`, `user_name_attribute`, `per_page`, `routes`, `api.*`, `mcp.*` (`enabled`, `path`, `middleware`, `local`, `local_user`, `allow_writes`), `markdown.extensions`, `markdown.options`. Publish with `php artisan vendor:publish --tag=wiki-config`; views with `--tag=wiki-views`.

### Tests

Use the factory; it creates a real first revision and indexes links.

@verbatim
<code-snippet name="Wiki pages in tests" lang="php">
use Ruvelo\Wiki\Models\Page;

$page = Page::factory()->titled('Deploy guide')->linkingTo('Ops', 'Billing')->create();
$this->get(route('wiki.show', $page))->assertOk();
</code-snippet>
@endverbatim

Import a folder of Markdown with `php artisan wiki:import path/ --user=1` and export with `php artisan wiki:export storage/wiki`.
