@extends('wiki::layout')

@section('title', $page->title)

@if (count($outline) > 1)
    @section('outline')
        <h2>On this page</h2>
        <ul>
            @foreach ($outline as $heading)
                <li @class(['is-sub' => $heading['level'] === 3])><a href="#{{ $heading['id'] }}">{{ $heading['text'] }}</a></li>
            @endforeach
        </ul>
    @endsection
@endif

@section('content')
    <article>
        <div class="wiki-head">
            <h1>{{ $page->title }}</h1>
            <div class="wiki-actions">
                @if ($canEdit)
                    <a href="{{ route('wiki.edit', $page) }}">Edit</a>
                @endif
                <a href="{{ route('wiki.history', $page) }}">History</a>
            </div>
        </div>

        <p class="wiki-meta">
            @if ($author)
                <span class="wiki-avatar" aria-hidden="true">{{ collect(explode(' ', $author))->map(fn ($part) => mb_substr($part, 0, 1))->take(2)->implode('') }}</span>
            @endif
            <span>Last edited <time datetime="{{ $page->updated_at->toIso8601String() }}">{{ $page->updated_at->diffForHumans() }}</time>@if ($author) by <strong>{{ $author }}</strong>@endif</span>
        </p>

        <div class="wiki-prose">
            @if (trim($page->body) === '')
                <p class="wiki-muted"><em>This page is empty.</em></p>
            @else
                {!! $html !!}
            @endif
        </div>

        @if ($backlinks->isNotEmpty())
            <aside class="wiki-backlinks">
                <h2>What links here</h2>
                <ul>
                    @foreach ($backlinks as $backlink)
                        <li><a href="{{ route('wiki.show', $backlink) }}">{{ $backlink->title }}</a></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </article>
@endsection
