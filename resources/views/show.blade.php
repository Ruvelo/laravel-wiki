@extends('wiki::layout')

@section('title', $page->title)

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

        <p class="wiki-meta">Last edited <time datetime="{{ $page->updated_at->toIso8601String() }}">{{ $page->updated_at->diffForHumans() }}</time></p>

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
