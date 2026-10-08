@extends('lore::layout')

@section('title', $page->title)

@section('content')
    <article>
        <div class="lore-head">
            <h1>{{ $page->title }}</h1>
            <div class="lore-actions">
                @if ($canEdit)
                    <a href="{{ route('lore.edit', $page) }}">Edit</a>
                @endif
                <a href="{{ route('lore.history', $page) }}">History</a>
            </div>
        </div>

        <p class="lore-meta">Last edited <time datetime="{{ $page->updated_at->toIso8601String() }}">{{ $page->updated_at->diffForHumans() }}</time></p>

        <div class="lore-prose">
            @if (trim($page->body) === '')
                <p class="lore-muted"><em>This page is empty.</em></p>
            @else
                {!! $html !!}
            @endif
        </div>

        @if ($backlinks->isNotEmpty())
            <aside class="lore-backlinks">
                <h2>What links here</h2>
                <ul>
                    @foreach ($backlinks as $backlink)
                        <li><a href="{{ route('lore.show', $backlink) }}">{{ $backlink->title }}</a></li>
                    @endforeach
                </ul>
            </aside>
        @endif
    </article>
@endsection
