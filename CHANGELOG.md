# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.2.0] - 2026-10-08

### Added

- An MCP server, so AI agents (Claude Code, Claude Desktop, Cursor, ChatGPT...) can use the wiki as a knowledge source. Tools: `search_pages`, `read_page`, `list_pages` and `recent_changes`, plus every page as a `wiki://pages/{slug}` resource with slug completion. Over HTTP at `/mcp/wiki` behind `auth:sanctum`, or locally with `php artisan mcp:start wiki`. Optional: it needs `laravel/mcp` and `WIKI_MCP=true`.
- `write_page` for agents, off unless `wiki.mcp.allow_writes` is true. It needs the `wiki-edit` gate, records an edit summary, and refuses to overwrite a page the agent hasn't read or that changed since.
- `mcp.*` config keys: `enabled`, `path`, `middleware`, `local`, `local_user`, `allow_writes`.
- Laravel Boost guidelines (`resources/boost/guidelines/core.blade.php`): coding agents in apps using Boost learn the PHP API, the single write path, events, the JSON API, config, the Sidebar page and the test factory.
- `Wiki::write()` takes an optional `basedOn` revision and throws `EditConflict` instead of overwriting a newer save.

## [1.1.0] - 2026-10-08

### Added

- Docs-style layout: a left-hand menu, the page, and an "On this page" outline on the right. The menu is an editable page (`wiki.sidebar`, "Sidebar" by default), with an alphabetical list as the fallback.

### Fixed

- Guests opening an edit URL in an app without a `login` route (a fresh install, before a starter kit) got a 500. They now get a 403, or are sent to the login page when the app has one. `edit_middleware` now defaults to `[]`, since the wiki checks sign-in itself.

### Changed

- The demo is now the project's homepage; the separate website is gone, and the README is the documentation.

## [1.0.0] - 2026-10-08

First release.

### Added

- Markdown pages with `[[wiki links]]`, `[[Page|label]]`, `[[Page#Section]]` and `[[#Section]]`; red links for pages not written yet.
- "What links here" backlinks on every page.
- Revision history with collapsed line diffs and one-click restore.
- Edit-conflict protection: a save based on an old revision is refused, never silently overwrites.
- Side-by-side editor with a live preview.
- Search with highlighted snippets; an exact title jumps straight to the page.
- PHP API: `Wiki::write()`, `create()`, `find()`, `search()`, `render()`, `extendMarkdown()`.
- Opt-in JSON REST API for pages and revisions, with `409` conflict responses.
- `wiki:import` and `wiki:export` Artisan commands for folders of Markdown (Obsidian vaults, docs folders, GitHub wikis).
- `PageSaved` and `PageDeleted` events; `EditConflict`, `PageAlreadyExists` and `InvalidTitle` exceptions.
- `Page::factory()` for tests in host apps.
- Configurable CommonMark extensions and options.
- Ruvelo house style UI: light and dark, no build step, themable through CSS variables.

[Unreleased]: https://github.com/Ruvelo/laravel-wiki/compare/v1.2.0...HEAD
[1.2.0]: https://github.com/Ruvelo/laravel-wiki/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/Ruvelo/laravel-wiki/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/Ruvelo/laravel-wiki/releases/tag/v1.0.0
