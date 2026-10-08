<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('lore.name') }}</title>
    <style>
        :root {
            --lore-bg: #f8f6f1;
            --lore-surface: #ffffff;
            --lore-ink: #1f1d18;
            --lore-muted: #6d675b;
            --lore-line: #e6e1d6;
            --lore-accent: #0d6b68;
            --lore-accent-soft: #e2f1ef;
            --lore-new: #b42318;
            --lore-add: #e6f4ea;
            --lore-add-ink: #1a5e2a;
            --lore-del: #fdecea;
            --lore-del-ink: #8c1d18;
            --lore-code: #f1eee6;
            --lore-serif: "Iowan Old Style", "Palatino Linotype", Palatino, Georgia, serif;
            --lore-sans: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --lore-mono: ui-monospace, "SF Mono", Menlo, Consolas, monospace;
            color-scheme: light;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --lore-bg: #14130f;
                --lore-surface: #1c1b17;
                --lore-ink: #ebe6da;
                --lore-muted: #a29b8c;
                --lore-line: #34312a;
                --lore-accent: #5cc6c0;
                --lore-accent-soft: #17302f;
                --lore-new: #ff8f84;
                --lore-add: #15301d;
                --lore-add-ink: #9ee0ae;
                --lore-del: #3a1714;
                --lore-del-ink: #ffaaa3;
                --lore-code: #24221d;
                color-scheme: dark;
            }
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: var(--lore-bg); color: var(--lore-ink); font: 16px/1.6 var(--lore-sans); }
        a { color: var(--lore-accent); text-underline-offset: 2px; }
        .lore-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.25rem; padding: .75rem max(16px, calc((100% - 56rem) / 2 + 16px)); border-bottom: 1px solid var(--lore-line); background: var(--lore-surface); }
        .lore-brand { font: 600 1.2rem var(--lore-serif); color: var(--lore-ink); text-decoration: none; letter-spacing: .01em; }
        .lore-search { flex: 1 1 12rem; }
        .lore-search input { width: 100%; }
        .lore-bar nav { display: flex; flex-wrap: wrap; gap: .25rem 1rem; align-items: center; font-size: .925rem; }
        .lore-bar nav a { text-decoration: none; }
        .lore-main { max-width: 56rem; margin: 0 auto; padding: 2rem 16px 4rem; }
        .lore-foot { max-width: 56rem; margin: 0 auto; padding: 0 16px 2rem; color: var(--lore-muted); font-size: .8rem; }
        h1, h2, h3, h4 { font-family: var(--lore-serif); line-height: 1.25; font-weight: 600; }
        h1 { font-size: 2.1rem; margin: 0; overflow-wrap: anywhere; }
        .lore-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: .5rem 1rem; padding-bottom: .5rem; border-bottom: 1px solid var(--lore-line); }
        .lore-actions { display: flex; gap: 1rem; font-size: .925rem; }
        .lore-meta { color: var(--lore-muted); font-size: .85rem; margin: .5rem 0 1.5rem; }
        .lore-flash { background: var(--lore-accent-soft); border-left: 3px solid var(--lore-accent); padding: .6rem .9rem; border-radius: 4px; margin: 0 0 1.5rem; }
        .lore-btn, button { display: inline-block; font: 500 .9rem var(--lore-sans); padding: .45rem .9rem; border-radius: 6px; border: 1px solid var(--lore-accent); background: var(--lore-accent); color: var(--lore-surface); text-decoration: none; cursor: pointer; }
        .lore-btn--quiet, button.lore-btn--quiet { background: transparent; color: var(--lore-accent); }
        .lore-btn--danger, button.lore-btn--danger { background: transparent; color: var(--lore-new); border-color: var(--lore-new); }
        input[type=text], input[type=search], textarea { font: inherit; color: inherit; background: var(--lore-bg); border: 1px solid var(--lore-line); border-radius: 6px; padding: .5rem .7rem; width: 100%; }
        input:focus, textarea:focus { outline: 2px solid var(--lore-accent); outline-offset: -1px; }
        textarea { font: .925rem/1.55 var(--lore-mono); min-height: 24rem; resize: vertical; }
        label { display: block; font-weight: 600; font-size: .875rem; margin: 1.25rem 0 .35rem; }
        .lore-error { color: var(--lore-new); font-size: .875rem; margin: .35rem 0 0; }
        .lore-row { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-top: 1.25rem; }
        .lore-list { list-style: none; padding: 0; margin: 1rem 0; }
        .lore-list li { padding: .65rem 0; border-bottom: 1px solid var(--lore-line); display: flex; flex-wrap: wrap; justify-content: space-between; gap: .25rem 1rem; }
        .lore-list small, .lore-muted { color: var(--lore-muted); }
        .lore-snippet { flex-basis: 100%; color: var(--lore-muted); font-size: .9rem; }
        .lore-prose { font: 1.075rem/1.7 var(--lore-serif); overflow-wrap: break-word; }
        .lore-prose a.lore-link--new { color: var(--lore-new); text-decoration-style: dotted; }
        .lore-prose .heading-permalink { opacity: 0; margin-left: .35rem; text-decoration: none; font-family: var(--lore-sans); }
        .lore-prose :is(h1,h2,h3,h4,h5,h6):hover .heading-permalink { opacity: .6; }
        .lore-prose h2 { border-bottom: 1px solid var(--lore-line); padding-bottom: .25rem; margin-top: 2rem; }
        .lore-prose code { font: .875em var(--lore-mono); background: var(--lore-code); padding: .1em .35em; border-radius: 4px; }
        .lore-prose pre { background: var(--lore-code); padding: 1rem; border-radius: 6px; overflow-x: auto; }
        .lore-prose pre code { background: none; padding: 0; }
        .lore-prose blockquote { margin: 1rem 0; padding: .1rem 1rem; border-left: 3px solid var(--lore-line); color: var(--lore-muted); }
        .lore-prose table { border-collapse: collapse; display: block; overflow-x: auto; font-family: var(--lore-sans); font-size: .925rem; }
        .lore-prose th, .lore-prose td { border: 1px solid var(--lore-line); padding: .4rem .7rem; }
        .lore-prose img { max-width: 100%; }
        .lore-prose li:has(> input[type=checkbox]) { list-style: none; margin-left: -1.2rem; }
        .lore-toc { background: var(--lore-surface); border: 1px solid var(--lore-line); border-radius: 6px; padding: .75rem 1rem .75rem 2rem; display: inline-block; font-family: var(--lore-sans); font-size: .925rem; }
        .lore-backlinks { margin-top: 3rem; padding-top: 1rem; border-top: 1px solid var(--lore-line); font-size: .925rem; }
        .lore-backlinks h2 { font-size: 1rem; margin: 0 0 .5rem; }
        .lore-backlinks ul { margin: 0; padding-left: 1.2rem; }
        .lore-diff { font: .85rem/1.5 var(--lore-mono); background: var(--lore-surface); border: 1px solid var(--lore-line); border-radius: 6px; overflow-x: auto; margin: 1rem 0; }
        .lore-diff div { padding: 0 .75rem; min-height: 1.5em; white-space: pre-wrap; overflow-wrap: anywhere; }
        .lore-diff .add { background: var(--lore-add); color: var(--lore-add-ink); }
        .lore-diff .del { background: var(--lore-del); color: var(--lore-del-ink); }
        .lore-diff .eq { color: var(--lore-muted); }
        .lore-add { color: var(--lore-add-ink); } .lore-del { color: var(--lore-del-ink); }
        .lore-preview { background: var(--lore-surface); border: 1px dashed var(--lore-line); border-radius: 6px; padding: 0 1.25rem; margin-top: 1.5rem; }
        details.lore-help { margin-top: 1rem; font-size: .9rem; color: var(--lore-muted); }
        details.lore-help code { font-family: var(--lore-mono); }
        nav[role=navigation] { margin-top: 1.5rem; font-size: .9rem; }
        nav[role=navigation] svg { width: 1rem; height: 1rem; }
    </style>
    @stack('lore-head')
</head>
<body>
    <header class="lore-bar">
        <a class="lore-brand" href="{{ route('lore.home') }}">{{ config('lore.name') }}</a>
        <form class="lore-search" action="{{ route('lore.search') }}" method="get" role="search">
            <input type="search" name="q" value="{{ request()->routeIs('lore.search') ? request('q') : '' }}" placeholder="Search pages…" aria-label="Search pages">
        </form>
        <nav>
            <a href="{{ route('lore.index') }}">All pages</a>
            <a href="{{ route('lore.recent') }}">Recent changes</a>
            @if ($canEdit)
                <a class="lore-btn" href="{{ route('lore.create') }}">New page</a>
            @endif
        </nav>
    </header>

    <main class="lore-main">
        @if (session('lore.status'))
            <p class="lore-flash" role="status">{{ session('lore.status') }}</p>
        @endif

        @yield('content')
    </main>

    <footer class="lore-foot">Powered by <a href="https://github.com/francoisbultez/lore">Lore</a></footer>
</body>
</html>
