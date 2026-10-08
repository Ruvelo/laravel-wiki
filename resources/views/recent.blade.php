@extends('lore::layout')

@section('title', 'Recent changes')

@section('content')
    <div class="lore-head">
        <h1>Recent changes</h1>
    </div>

    @if ($revisions->isEmpty())
        <p class="lore-muted">No edits yet.</p>
    @else
        <ul class="lore-list">
            @foreach ($revisions as $revision)
                <li>
                    <span>
                        <a href="{{ route('lore.show', $revision->page) }}">{{ $revision->page->title }}</a>
                        · <a href="{{ route('lore.revision', [$revision->page, $revision]) }}">#{{ $revision->id }}</a>
                        <span class="lore-muted">{{ $revision->summary }}</span>
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
