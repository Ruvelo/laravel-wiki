# Contributing to Laravel Wiki

Thanks for helping! Bug reports, docs fixes and features are all welcome.

## Before you start

- **Bugs:** [open an issue](https://github.com/Ruvelo/laravel-wiki/issues/new/choose) with the steps to reproduce, or go straight to a pull request with a failing test.
- **Features:** open an issue first if it's more than a small change, so we can agree on the shape before you spend time on it.
- **Security issues:** don't open an issue. See [SECURITY.md](https://github.com/Ruvelo/.github/blob/main/SECURITY.md).

## Setup

You need PHP 8.3+ and Composer. No database server: tests run on in-memory SQLite.

```bash
git clone https://github.com/<you>/laravel-wiki && cd laravel-wiki
composer install
composer check
```

`composer check` runs exactly what CI runs:

| Command | What it does |
|---|---|
| `composer test` | PHPUnit, through Orchestra Testbench |
| `composer lint` | Code style check (Laravel Pint) |
| `composer format` | Fix code style |
| `composer analyse` | Static analysis (PHPStan level 8 with Larastan) |
| `composer demo` | Build the static demo into `build/` |

## Making a change

1. Branch from `main`.
2. Write a test that fails without your change. Feature tests live in `tests/Feature`, unit tests in `tests/Unit`.
3. Keep the public API stable: `Ruvelo\Wiki\Wiki`, the models, events, exceptions, config keys, routes and the JSON shapes. If you must change one, say so in the PR.
4. Run `composer format` and `composer check`.
5. Add a line under **Unreleased** in [CHANGELOG.md](CHANGELOG.md).
6. Open the pull request. Screenshots help for anything visual.

## Where things live

```
src/Wiki.php                 The public PHP API
src/Models/                  Page and Revision; Page::commit() is the one write path
src/Markdown/                Rendering and [[wiki link]] parsing
src/Http/Controllers/        Web UI, and Api/ for the JSON API
src/Console/                 wiki:import and wiki:export
resources/views/             Blade views; the styles live in layout.blade.php
site/                        The website, deployed to GitHub Pages
demo/                        The demo wiki's content, build script and screenshots
```

## Style

- `declare(strict_types=1)` everywhere, typed properties and return types.
- No `@phpstan-ignore` or baseline entries: fix the cause.
- Comments explain *why*, not *what*.
- UI follows the [Ruvelo house style](https://github.com/Ruvelo/.github/blob/main/BRAND.md): CSS variables, no build step, works without JavaScript, light and dark.

By contributing you agree that your work is released under the [MIT license](LICENSE) and that you'll follow the [code of conduct](https://github.com/Ruvelo/.github/blob/main/CODE_OF_CONDUCT.md).
