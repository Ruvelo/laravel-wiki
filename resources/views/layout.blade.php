<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · {{ config('wiki.name') }}</title>
    <style>
        :root {
            --wiki-bg: #ffffff;
            --wiki-surface: #f5f7fb;
            --wiki-ink: #1a1d24;
            --wiki-muted: #5d6574;
            --wiki-line: #e3e7ee;
            --wiki-accent: #2251d1;
            --wiki-accent-soft: #e9effd;
            --wiki-on-accent: #ffffff;
            --wiki-new: #c8322a;
            --wiki-add: #e7f6ec;
            --wiki-add-ink: #17643a;
            --wiki-del: #fdeceb;
            --wiki-del-ink: #9b211a;
            --wiki-code: #f3f5f9;
            --wiki-serif: Charter, "Bitstream Charter", "Iowan Old Style", "Sitka Text", Cambria, Georgia, serif;
            --wiki-sans: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
            --wiki-mono: ui-monospace, "SF Mono", "Cascadia Code", Menlo, Consolas, monospace;
            color-scheme: light;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --wiki-bg: #0f1420;
                --wiki-surface: #161c2b;
                --wiki-ink: #e6e9f0;
                --wiki-muted: #9aa3b5;
                --wiki-line: #263048;
                --wiki-accent: #8fb0ff;
                --wiki-accent-soft: #1b2745;
                --wiki-on-accent: #0f1420;
                --wiki-new: #ff8277;
                --wiki-add: #12301f;
                --wiki-add-ink: #98e2b0;
                --wiki-del: #3a1715;
                --wiki-del-ink: #ffaba4;
                --wiki-code: #1a2132;
                color-scheme: dark;
            }
        }
        *, *::before, *::after { box-sizing: border-box; }
        body { margin: 0; background: var(--wiki-bg); color: var(--wiki-ink); font: 16px/1.6 var(--wiki-sans); }
        a { color: var(--wiki-accent); text-underline-offset: 2px; }
        .wiki-bar { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem 1.25rem; padding: .75rem max(16px, calc((100% - 56rem) / 2 + 16px)); border-bottom: 1px solid var(--wiki-line); background: var(--wiki-surface); }
        .wiki-brand { font: 700 1.1rem var(--wiki-sans); color: var(--wiki-ink); text-decoration: none; letter-spacing: -.01em; }
        .wiki-brand::before { content: "[["; color: var(--wiki-accent); margin-right: .15em; font-weight: 500; }
        .wiki-brand::after { content: "]]"; color: var(--wiki-accent); margin-left: .15em; font-weight: 500; }
        .wiki-search { flex: 1 1 12rem; }
        .wiki-search input { width: 100%; }
        .wiki-bar nav { display: flex; flex-wrap: wrap; gap: .25rem 1rem; align-items: center; font-size: .925rem; }
        .wiki-bar nav a { text-decoration: none; }
        .wiki-main { max-width: 56rem; margin: 0 auto; padding: 2rem 16px 4rem; }
        .wiki-main--wide { max-width: 84rem; }
        body:has(.wiki-main--wide) .wiki-bar { padding-inline: max(16px, calc((100% - 84rem) / 2 + 16px)); }
        .wiki-editor { display: grid; gap: 1rem 2.5rem; }
        .wiki-editor-preview { min-width: 0; border-top: 1px solid var(--wiki-line); }
        @media (min-width: 64rem) {
            .wiki-editor { grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); }
            .wiki-editor-preview { border-top: 0; border-left: 1px solid var(--wiki-line); padding-left: 2.5rem; }
            .wiki-editor textarea { min-height: 62vh; }
        }
        .wiki-foot { max-width: 56rem; margin: 0 auto; padding: 0 16px 2rem; color: var(--wiki-muted); font-size: .8rem; }
        h1, h2, h3, h4 { font-family: var(--wiki-serif); line-height: 1.25; font-weight: 600; }
        h1 { font-size: 2.25rem; margin: 0; overflow-wrap: anywhere; letter-spacing: -.005em; }
        .wiki-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; gap: .5rem 1rem; padding-bottom: .5rem; border-bottom: 1px solid var(--wiki-line); }
        .wiki-actions { display: flex; gap: 1rem; font-size: .925rem; }
        .wiki-meta { color: var(--wiki-muted); font-size: .85rem; margin: .5rem 0 1.5rem; }
        .wiki-flash { background: var(--wiki-accent-soft); border-left: 3px solid var(--wiki-accent); padding: .6rem .9rem; border-radius: 4px; margin: 0 0 1.5rem; }
        .wiki-btn, button { display: inline-block; font: 500 .9rem var(--wiki-sans); padding: .45rem .9rem; border-radius: 6px; border: 1px solid var(--wiki-accent); background: var(--wiki-accent); color: var(--wiki-on-accent); text-decoration: none; cursor: pointer; }
        .wiki-btn--quiet, button.wiki-btn--quiet { background: transparent; color: var(--wiki-accent); }
        .wiki-btn--danger, button.wiki-btn--danger { background: transparent; color: var(--wiki-new); border-color: var(--wiki-new); }
        input[type=text], input[type=search], textarea { font: inherit; color: inherit; background: var(--wiki-bg); border: 1px solid var(--wiki-line); border-radius: 6px; padding: .5rem .7rem; width: 100%; }
        input:focus, textarea:focus { outline: 2px solid var(--wiki-accent); outline-offset: -1px; }
        textarea { font: .925rem/1.55 var(--wiki-mono); min-height: 24rem; resize: vertical; }
        label { display: block; font-weight: 600; font-size: .875rem; margin: 1.25rem 0 .35rem; }
        .wiki-error { color: var(--wiki-new); font-size: .875rem; margin: .35rem 0 0; }
        .wiki-row { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-top: 1.25rem; }
        .wiki-list { list-style: none; padding: 0; margin: 1rem 0; }
        .wiki-list li { padding: .65rem 0; border-bottom: 1px solid var(--wiki-line); display: flex; flex-wrap: wrap; justify-content: space-between; gap: .25rem 1rem; }
        .wiki-list small, .wiki-muted { color: var(--wiki-muted); }
        .wiki-snippet { flex-basis: 100%; color: var(--wiki-muted); font-size: .9rem; }
        .wiki-snippet mark { background: var(--wiki-accent-soft); color: var(--wiki-ink); border-radius: 2px; padding: 0 .1em; }
        .wiki-prose { font-size: 1.0625rem; line-height: 1.7; overflow-wrap: break-word; }
        .wiki-prose a { text-decoration-color: color-mix(in srgb, currentColor 35%, transparent); }
        .wiki-prose a:hover { text-decoration-color: currentColor; }
        .wiki-prose a.wiki-link--new { color: var(--wiki-new); text-decoration-style: dotted; text-decoration-color: currentColor; }
        .wiki-prose .heading-permalink { opacity: 0; margin-left: .35rem; text-decoration: none; font-family: var(--wiki-sans); }
        .wiki-prose :is(h1,h2,h3,h4,h5,h6):hover .heading-permalink { opacity: .6; }
        .wiki-prose h2 { border-bottom: 1px solid var(--wiki-line); padding-bottom: .25rem; margin-top: 2rem; }
        .wiki-prose code { font: .875em var(--wiki-mono); background: var(--wiki-code); padding: .1em .35em; border-radius: 4px; }
        .wiki-prose pre { background: var(--wiki-code); padding: .9rem 1rem; border-radius: 6px; overflow-x: auto; line-height: 1.5; }
        .wiki-prose pre code { background: none; padding: 0; }
        .wiki-prose blockquote { margin: 1rem 0; padding: .1rem 1rem; border-left: 3px solid var(--wiki-line); color: var(--wiki-muted); }
        .wiki-prose table { border-collapse: collapse; display: block; overflow-x: auto; font-family: var(--wiki-sans); font-size: .925rem; }
        .wiki-prose th, .wiki-prose td { border: 1px solid var(--wiki-line); padding: .4rem .7rem; }
        .wiki-prose img { max-width: 100%; }
        .wiki-prose li:has(> input[type=checkbox]) { list-style: none; margin-left: -1.2rem; }
        .wiki-toc { background: var(--wiki-surface); border-left: 3px solid var(--wiki-accent); border-radius: 0 6px 6px 0; padding: .75rem 1.25rem .75rem 2rem; display: inline-block; font-size: .925rem; }
        .wiki-backlinks { margin-top: 3rem; padding-top: 1rem; border-top: 1px solid var(--wiki-line); font-size: .925rem; }
        .wiki-backlinks h2 { font-size: 1rem; margin: 0 0 .5rem; }
        .wiki-backlinks ul { margin: 0; padding-left: 1.2rem; }
        .wiki-diff { font: .85rem/1.5 var(--wiki-mono); background: var(--wiki-bg); border: 1px solid var(--wiki-line); border-radius: 6px; overflow-x: auto; margin: 1rem 0; }
        .wiki-diff div { padding: 0 .75rem; min-height: 1.5em; white-space: pre-wrap; overflow-wrap: anywhere; }
        .wiki-diff .add { background: var(--wiki-add); color: var(--wiki-add-ink); }
        .wiki-diff .del { background: var(--wiki-del); color: var(--wiki-del-ink); }
        .wiki-diff .eq { color: var(--wiki-muted); }
        .wiki-diff .skip { color: var(--wiki-muted); background: var(--wiki-surface); font-family: var(--wiki-sans); font-size: .8rem; padding-block: .3rem; }
        .wiki-add { color: var(--wiki-add-ink); } .wiki-del { color: var(--wiki-del-ink); }
        details.wiki-help { margin-top: 1rem; font-size: .9rem; color: var(--wiki-muted); }
        details.wiki-help code { font-family: var(--wiki-mono); }
        nav[role=navigation] { margin-top: 1.5rem; font-size: .9rem; }
        nav[role=navigation] svg { width: 1rem; height: 1rem; }
    </style>
    @stack('wiki-head')
</head>
<body>
    <header class="wiki-bar">
        <a class="wiki-brand" href="{{ route('wiki.home') }}">{{ config('wiki.name') }}</a>
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
