@extends('lore::layout')

@section('title', 'History of '.$page->title)

@section('content')
    <div class="lore-head">
        <h1>History of <a href="{{ route('lore.show', $page) }}">{{ $page->title }}</a></h1>
    </div>

    <ul class="lore-list">
        @foreach ($revisions as $revision)
            <li>
                <span>
                    <a href="{{ route('lore.revision', [$page, $revision]) }}">#{{ $revision->id }}</a>
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
