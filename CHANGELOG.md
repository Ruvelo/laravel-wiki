# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- Docs-style layout: a left-hand menu, the page, and an "On this page" outline on the right. The menu is an editable page (`wiki.sidebar`, "Sidebar" by default), with an alphabetical list as the fallback.

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

[Unreleased]: https://github.com/Ruvelo/laravel-wiki/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Ruvelo/laravel-wiki/releases/tag/v1.0.0
