@extends('wiki::layout')

@section('title', $query === '' ? 'Search' : 'Search: '.$query)

@section('content')
    <div class="wiki-head">
        <h1>{{ $query === '' ? 'Search' : 'Results for “'.$query.'”' }}</h1>
    </div>

    @if ($pages === null)
        <p class="wiki-muted">Type in the search box above.</p>
    @elseif ($pages->isEmpty())
        <p>Nothing matches.
            @if ($canEdit)
                <a href="{{ route('wiki.create', ['title' => $query]) }}">Create “{{ $query }}”?</a>
            @endif
        </p>
    @else
        <ul class="wiki-list">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ route('wiki.show', $page) }}">{{ $page->title }}</a>
                    <small>edited {{ $page->updated_at->diffForHumans() }}</small>
                    @if ($snippet = \Illuminate\Support\Str::excerpt($page->body, $query, ['radius' => 90]))
                        <span class="wiki-snippet">{{ $snippet }}</span>
                    @endif
                </li>
            @endforeach
        </ul>

        {{ $pages->links() }}
    @endif
@endsection
