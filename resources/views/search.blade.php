@extends('lore::layout')

@section('title', $query === '' ? 'Search' : 'Search: '.$query)

@section('content')
    <div class="lore-head">
        <h1>{{ $query === '' ? 'Search' : 'Results for “'.$query.'”' }}</h1>
    </div>

    @if ($pages === null)
        <p class="lore-muted">Type in the search box above.</p>
    @elseif ($pages->isEmpty())
        <p>Nothing matches.
            @if ($canEdit)
                <a href="{{ route('lore.create', ['title' => $query]) }}">Create “{{ $query }}”?</a>
            @endif
        </p>
    @else
        <ul class="lore-list">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ route('lore.show', $page) }}">{{ $page->title }}</a>
                    <small>edited {{ $page->updated_at->diffForHumans() }}</small>
                    @if ($snippet = \Illuminate\Support\Str::excerpt($page->body, $query, ['radius' => 90]))
                        <span class="lore-snippet">{{ $snippet }}</span>
                    @endif
                </li>
            @endforeach
        </ul>

        {{ $pages->links() }}
    @endif
@endsection
