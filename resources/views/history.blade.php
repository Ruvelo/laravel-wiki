@extends('wiki::layout')

@section('title', 'History of '.$page->title)

@section('content')
    <div class="wiki-head">
        <h1>History of <a href="{{ route('wiki.show', $page) }}">{{ $page->title }}</a></h1>
    </div>

    <ul class="wiki-list">
        @foreach ($revisions as $revision)
            <li>
                <span>
                    <a href="{{ route('wiki.revision', [$page, $revision]) }}">#{{ $revision->id }}</a>
                    {{ $revision->summary ?: '—' }}
                    @if ($loop->first && $revisions->onFirstPage())
                        <small>(current)</small>
                    @endif
                </span>
                <small>
                    {{ $revision->authorName() ?? 'someone' }},
                    <time datetime="{{ $revision->created_at->toIso8601String() }}" title="{{ $revision->created_at->toDayDateTimeString() }}">{{ $revision->created_at->diffForHumans() }}</time>
                </small>
            </li>
        @endforeach
    </ul>

    {{ $revisions->links() }}
@endsection
