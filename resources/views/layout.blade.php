<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('wiki.name') }}</title>
    <style>
        /* Ruvelo house style. Override any --wiki-* variable to re-theme. */
        :root {
            --wiki-bg: #ffffff;
            --wiki-subtle: #f8f8fc;
            --wiki-muted: #f0f0f7;
            --wiki-line: #e4e4ef;
            --wiki-ink: #16162a;
            --wiki-text-2: #4b4b63;
            --wiki-text-3: #74748b;
            --wiki-accent: #3d4eff;
            --wiki-accent-2: #a78bfa;
            --wiki-accent-ink: #2b38d6;
            --wiki-accent-soft: #eef0ff;
            --wiki-on-accent: #ffffff;
            --wiki-new: #e5384f;
            --wiki-add: #e8f8ef;
            --wiki-add-ink: #167a4a;
            --wiki-del: #fdecef;
            --wiki-del-ink: #c4283f;
            --wiki-radius: 8px;
            --wiki-radius-lg: 14px;
            --wiki-sans: "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --wiki-mono: "Geist Mono", ui-monospace, "SF Mono", "Cascadia Code", Menlo, Consolas, monospace;
            color-scheme: light;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --wiki-bg: #11111c;
                --wiki-subtle: #171725;
                --wiki-muted: #1f1f30;
                --wiki-line: #2a2a3f;
                --wiki-ink: #f1f1f8;
                --wiki-text-2: #b6b6cc;
                --wiki-text-3: #8787a3;
                --wiki-accent: #8f9bff;
                --wiki-accent-2: #c4b5fd;
                --wiki-accent-ink: #b3bbff;
                --wiki-accent-soft: #1e2150;
                --wiki-on-accent: #0b0b1a;
                --wiki-new: #ff6b80;
                --wiki-add: #0f2b1f;
                --wiki-add-ink: #74d6a2;
                --wiki-del: #331520;
                --wiki-del-ink: #ff9cab;
                color-scheme: dark;
            }
        }
        *, *::before, *::after { box-sizing: border-box; }
        body::before { content: ""; position: fixed; inset: 0 0 auto; height: 3px; z-index: 20; background: linear-gradient(90deg, var(--wiki-accent), var(--wiki-accent-2)); }
        body { margin: 0; background: var(--wiki-bg); color: var(--wiki-ink); font: 15px/1.6 var(--wiki-sans); -webkit-font-smoothing: antialiased; }
        a { color: var(--wiki-accent); text-decoration: none; }
        a:hover { text-decoration: underline; text-underline-offset: 3px; }
        :focus-visible { outline: 2px solid var(--wiki-accent); outline-offset: 2px; border-radius: 4px; }

        .wiki-bar { position: sticky; top: 0; z-index: 10; display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.5rem; padding: .75rem max(16px, calc((100% - 52rem) / 2 + 16px)); background: color-mix(in srgb, var(--wiki-bg) 88%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid var(--wiki-line); }
        .wiki-brand { display: inline-flex; align-items: center; gap: .6rem; font-weight: 600; font-size: .95rem; color: var(--wiki-ink); letter-spacing: -.01em; }
        .wiki-mark { display: grid; place-items: center; width: 1.75rem; height: 1.75rem; border-radius: 7px; background: linear-gradient(135deg, var(--wiki-accent), var(--wiki-accent-2)); color: #fff; font-size: .85rem; font-weight: 700; box-shadow: 0 4px 12px -4px color-mix(in srgb, var(--wiki-accent) 60%, transparent); }
        .wiki-brand:hover { text-decoration: none; }
        .wiki-search { flex: 1 1 12rem; position: relative; }
        .wiki-search::before { content: ""; position: absolute; left: .75rem; top: 50%; width: .8rem; height: .8rem; translate: 0 -50%; background: var(--wiki-text-3); -webkit-mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M7 1.5a5.5 5.5 0 0 1 4.38 8.83l3.15 3.14-1.06 1.06-3.14-3.15A5.5 5.5 0 1 1 7 1.5Zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z'/%3E%3C/svg%3E") center / contain no-repeat; mask: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath d='M7 1.5a5.5 5.5 0 0 1 4.38 8.83l3.15 3.14-1.06 1.06-3.14-3.15A5.5 5.5 0 1 1 7 1.5Zm0 1.5a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z'/%3E%3C/svg%3E") center / contain no-repeat; }
        .wiki-search input[type=search] { padding-left: 2.1rem; background: var(--wiki-muted); border-color: transparent; }
        .wiki-bar nav { display: flex; flex-wrap: wrap; gap: .25rem 1.25rem; align-items: center; font-size: .9rem; }
        .wiki-bar nav a:not(.wiki-btn) { color: var(--wiki-text-2); }
        .wiki-bar nav a:not(.wiki-btn):hover { color: var(--wiki-ink); text-decoration: none; }

        .wiki-main { max-width: 52rem; margin: 0 auto; padding: 2.5rem 16px 4rem; }
        .wiki-main--wide { max-width: 84rem; }
        body:has(.wiki-main--wide) .wiki-bar { padding-inline: max(16px, calc((100% - 84rem) / 2 + 16px)); }
        .wiki-foot { max-width: 52rem; margin: 0 auto; padding: 0 16px 2.5rem; color: var(--wiki-text-3); font-size: .8rem; }
        .wiki-foot a { color: inherit; text-decoration: underline; text-underline-offset: 2px; }

        h1, h2, h3, h4 { line-height: 1.25; font-weight: 600; letter-spacing: -.02em; }
        h1 { font-size: 2rem; margin: 0; overflow-wrap: anywhere; }
        h1 a { color: inherit; }
        .wiki-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: .5rem 1rem; }
        .wiki-actions { display: flex; gap: .5rem; }
        .wiki-actions a { font-size: .875rem; color: var(--wiki-text-2); border: 1px solid var(--wiki-line); border-radius: var(--wiki-radius); padding: .35rem .75rem; }
        .wiki-actions a:hover { color: var(--wiki-accent-ink); border-color: var(--wiki-accent); background: var(--wiki-accent-soft); text-decoration: none; }
        .wiki-meta { display: flex; align-items: center; gap: .5rem; color: var(--wiki-text-3); font-size: .85rem; margin: .75rem 0 2.25rem; }
        .wiki-meta strong { color: var(--wiki-text-2); font-weight: 500; }
        .wiki-avatar { display: grid; place-items: center; width: 1.5rem; height: 1.5rem; border-radius: 50%; background: var(--wiki-accent-soft); color: var(--wiki-accent-ink); font-size: .65rem; font-weight: 600; flex: none; }
        .wiki-flash { display: flex; align-items: center; gap: .6rem; background: var(--wiki-subtle); border: 1px solid var(--wiki-line); padding: .6rem .9rem; border-radius: var(--wiki-radius); margin: 0 0 1.75rem; font-size: .9rem; }
        .wiki-flash::before { content: ""; width: .5rem; height: .5rem; border-radius: 50%; background: var(--wiki-accent); flex: none; }

        .wiki-btn, button { display: inline-flex; align-items: center; font: 500 .875rem/1 var(--wiki-sans); padding: .6rem .95rem; border-radius: var(--wiki-radius); border: 1px solid var(--wiki-accent); background: var(--wiki-accent); color: var(--wiki-on-accent); cursor: pointer; box-shadow: 0 6px 16px -8px color-mix(in srgb, var(--wiki-accent) 70%, transparent); }
        .wiki-btn:hover, button:hover { background: var(--wiki-accent-ink); border-color: var(--wiki-accent-ink); text-decoration: none; }
        button.wiki-btn--quiet { background: transparent; color: var(--wiki-ink); border-color: var(--wiki-line); box-shadow: none; }
        button.wiki-btn--quiet:hover { background: var(--wiki-subtle); }
        button.wiki-btn--danger { background: transparent; color: var(--wiki-new); border-color: var(--wiki-line); box-shadow: none; }
        button.wiki-btn--danger:hover { background: var(--wiki-del); border-color: var(--wiki-new); }
        input[type=text], input[type=search], textarea { font: inherit; color: inherit; background: var(--wiki-bg); border: 1px solid var(--wiki-line); border-radius: var(--wiki-radius); padding: .55rem .75rem; width: 100%; }
        input:focus, textarea:focus { outline: none; border-color: var(--wiki-accent); box-shadow: 0 0 0 3px var(--wiki-accent-soft); }
        textarea { font: .875rem/1.65 var(--wiki-mono); min-height: 24rem; resize: vertical; }
        label { display: block; font-weight: 500; font-size: .85rem; margin: 1.25rem 0 .4rem; }
        .wiki-error { color: var(--wiki-new); font-size: .85rem; margin: .4rem 0 0; }
        .wiki-row { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; margin-top: 1.25rem; }
        .wiki-muted { color: var(--wiki-text-3); }
        .wiki-list { list-style: none; padding: 0; margin: 1.5rem 0; border-top: 1px solid var(--wiki-line); }
        .wiki-list li { padding: .8rem 0; border-bottom: 1px solid var(--wiki-line); display: flex; flex-wrap: wrap; justify-content: space-between; gap: .25rem 1rem; }
        .wiki-list a { font-weight: 500; }
        .wiki-list li:hover { background: linear-gradient(90deg, var(--wiki-accent-soft), transparent 60%); }
        .wiki-list small { color: var(--wiki-text-3); }
        .wiki-snippet { flex-basis: 100%; color: var(--wiki-text-2); font-size: .875rem; }
        .wiki-snippet mark { background: var(--wiki-accent-soft); color: var(--wiki-ink); border-radius: 3px; padding: 0 .15em; }

        .wiki-prose { font-size: 1rem; line-height: 1.75; overflow-wrap: break-word; color: var(--wiki-ink); }
        .wiki-prose > :first-child { margin-top: 0; }
        .wiki-prose h2 { font-size: 1.375rem; margin: 2.5rem 0 .75rem; }
        .wiki-prose h3 { font-size: 1.125rem; margin: 1.75rem 0 .5rem; }
        .wiki-prose a { text-decoration: underline; text-decoration-color: color-mix(in srgb, currentColor 30%, transparent); text-underline-offset: 3px; }
        .wiki-prose a:hover { text-decoration-color: currentColor; background: var(--wiki-accent-soft); border-radius: 3px; }
        .wiki-prose a.wiki-link--new { color: var(--wiki-new); text-decoration-style: dashed; text-decoration-color: currentColor; }
        .wiki-prose .heading-permalink { opacity: 0; margin-left: .4rem; color: var(--wiki-text-3); text-decoration: none; font-weight: 400; }
        .wiki-prose :is(h1,h2,h3,h4,h5,h6):hover .heading-permalink { opacity: 1; }
        .wiki-prose code { font: .85em var(--wiki-mono); background: var(--wiki-muted); padding: .15em .4em; border-radius: 5px; }
        .wiki-prose pre { background: var(--wiki-subtle); border: 1px solid var(--wiki-line); padding: .9rem 1.1rem; border-radius: var(--wiki-radius-lg); overflow-x: auto; line-height: 1.55; }
        .wiki-prose pre code { background: none; padding: 0; font-size: .85rem; }
        .wiki-prose blockquote { margin: 1.5rem 0; padding: .6rem 1.1rem; border-left: 3px solid var(--wiki-accent); background: var(--wiki-subtle); border-radius: 0 var(--wiki-radius) var(--wiki-radius) 0; color: var(--wiki-text-2); }
        .wiki-prose blockquote p { margin: .3rem 0; }
        .wiki-prose table { border-collapse: collapse; display: block; overflow-x: auto; font-size: .9rem; }
        .wiki-prose th { background: var(--wiki-subtle); font-weight: 500; text-align: left; }
        .wiki-prose th, .wiki-prose td { border: 1px solid var(--wiki-line); padding: .45rem .8rem; }
        .wiki-prose img { max-width: 100%; border-radius: var(--wiki-radius); }
        .wiki-prose li:has(> input[type=checkbox]) { list-style: none; margin-left: -1.3rem; }
        .wiki-prose input[type=checkbox] { accent-color: var(--wiki-accent); margin-right: .35rem; }
        .wiki-toc { background: var(--wiki-accent-soft); border-radius: var(--wiki-radius-lg); padding: 2.4rem 1.5rem 1rem 2.4rem; display: inline-block; font-size: .9rem; margin: 0 0 .5rem; position: relative; min-width: 14rem; }
        .wiki-toc::before { content: "On this page"; position: absolute; top: .9rem; left: 1.25rem; font-size: .75rem; font-weight: 600; color: var(--wiki-accent-ink); }
        .wiki-toc li::marker { color: var(--wiki-accent); }
        .wiki-prose .wiki-toc a { text-decoration: none; }
        .wiki-backlinks { margin-top: 3rem; padding-top: 1.25rem; border-top: 1px solid var(--wiki-line); font-size: .9rem; }
        .wiki-backlinks h2 { font-size: .9rem; color: var(--wiki-text-3); font-weight: 500; letter-spacing: 0; margin: 0 0 .5rem; }
        .wiki-backlinks ul { margin: 0; padding: 0; list-style: none; display: flex; flex-wrap: wrap; gap: .4rem; }
        .wiki-backlinks a { display: inline-block; background: var(--wiki-accent-soft); border: 1px solid transparent; border-radius: 999px; padding: .25rem .8rem; color: var(--wiki-accent-ink); font-weight: 500; }
        .wiki-backlinks a:hover { border-color: var(--wiki-accent); color: var(--wiki-accent); text-decoration: none; }

        .wiki-diff { font: .8rem/1.6 var(--wiki-mono); background: var(--wiki-bg); border: 1px solid var(--wiki-line); border-radius: var(--wiki-radius-lg); overflow: hidden; margin: 1rem 0; }
        .wiki-diff div { padding: 0 .9rem; min-height: 1.6em; white-space: pre-wrap; overflow-wrap: anywhere; }
        .wiki-diff .add { background: var(--wiki-add); color: var(--wiki-add-ink); }
        .wiki-diff .del { background: var(--wiki-del); color: var(--wiki-del-ink); }
        .wiki-diff .eq { color: var(--wiki-text-3); }
        .wiki-diff .skip { color: var(--wiki-text-3); background: var(--wiki-subtle); font: .75rem var(--wiki-sans); padding-block: .35rem; }
        .wiki-add { color: var(--wiki-add-ink); } .wiki-del { color: var(--wiki-del-ink); }

        .wiki-editor { display: grid; gap: 1rem 2.5rem; }
        .wiki-editor-preview { min-width: 0; border-top: 1px solid var(--wiki-line); }
        @media (min-width: 64rem) {
            .wiki-editor { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
            .wiki-editor-preview { border-top: 0; border-left: 1px solid var(--wiki-line); padding-left: 2.5rem; }
            .wiki-editor textarea { min-height: 62vh; }
        }
        details.wiki-help { margin-top: .75rem; font-size: .85rem; color: var(--wiki-text-2); }
        details.wiki-help summary { cursor: pointer; color: var(--wiki-text-3); }
        details.wiki-help code { font-family: var(--wiki-mono); }
        nav[role=navigation] { margin-top: 1.5rem; font-size: .875rem; }
        nav[role=navigation] svg { width: 1rem; height: 1rem; }
    </style>
    @stack('wiki-head')
</head>
<body>
    <header class="wiki-bar">
        <a class="wiki-brand" href="{{ route('wiki.home') }}"><span class="wiki-mark" aria-hidden="true">{{ mb_strtoupper(mb_substr(config('wiki.name'), 0, 1)) }}</span>{{ config('wiki.name') }}</a>
        <form class="wiki-search" action="{{ route('wiki.search') }}" method="get" role="search">
            <input type="search" name="q" value="{{ request()->routeIs('wiki.search') ? request('q') : '' }}" placeholder="Search pages…" aria-label="Search pages">
        </form>
        <nav>
            <a href="{{ route('wiki.index') }}">All pages</a>
            <a href="{{ route('wiki.recent') }}">Recent changes</a>
            @if ($canEdit)
                <a class="wiki-btn" href="{{ route('wiki.create') }}">New page</a>
            @endif
        </nav>
    </header>

    <main class="wiki-main @hasSection('wide') wiki-main--wide @endif">
        @if (session('wiki.status'))
            <p class="wiki-flash" role="status">{{ session('wiki.status') }}</p>
        @endif

        @yield('content')
    </main>

    <footer class="wiki-foot">Powered by <a href="https://github.com/ruvelo/laravel-wiki">Laravel Wiki</a> by Ruvelo</footer>
</body>
</html>
