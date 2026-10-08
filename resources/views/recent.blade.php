@extends('wiki::layout')

@section('title', 'Recent changes')

@section('content')
    <div class="wiki-head">
        <h1>Recent changes</h1>
    </div>

    @if ($revisions->isEmpty())
        <p class="wiki-muted">No edits yet.</p>
    @else
        <ul class="wiki-list">
            @foreach ($revisions as $revision)
                <li>
                    <span>
                        <a href="{{ route('wiki.show', $revision->page) }}">{{ $revision->page->title }}</a>
                        · <a href="{{ route('wiki.revision', [$revision->page, $revision]) }}">#{{ $revision->id }}</a>
                        <span class="wiki-muted">{{ $revision->summary }}</span>
                    </span>
                    <small>
                        {{ $revision->authorName() ?? 'someone' }},
                        <time datetime="{{ $revision->created_at->toIso8601String() }}">{{ $revision->created_at->diffForHumans() }}</time>
                    </small>
                </li>
            @endforeach
        </ul>

        {{ $revisions->links() }}
    @endif
@endsection
