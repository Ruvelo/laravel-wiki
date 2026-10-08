@extends('lore::layout')

@section('title', $page->title.' · revision #'.$revision->id)

@section('content')
    <div class="lore-head">
        <h1><a href="{{ route('lore.show', $page) }}">{{ $page->title }}</a> · #{{ $revision->id }}</h1>
        <div class="lore-actions">
            <a href="{{ route('lore.history', $page) }}">All revisions</a>
        </div>
    </div>

    <p class="lore-meta">
        {{ $revision->authorName() ?? 'someone' }}, {{ $revision->created_at->toDayDateTimeString() }}
        @if ($revision->summary) · {{ $revision->summary }} @endif
        @if ($isCurrent) · <strong>current version</strong> @endif
    </p>

    @if ($canEdit && ! $isCurrent)
        <form method="post" action="{{ route('lore.restore', [$page, $revision]) }}">
            @csrf
            <button type="submit">Restore this version</button>
        </form>
    @endif

    <h2>
        {{ $previous ? 'Changes since #'.$previous->id : 'First version' }}
        <small class="lore-muted" style="font-size: .9rem; font-family: var(--lore-sans)">
            <span class="lore-add">+{{ $stats['added'] }}</span> <span class="lore-del">−{{ $stats['removed'] }}</span>
        </small>
    </h2>

    @if ($previous && $previous->title !== $revision->title)
        <p>Renamed from “{{ $previous->title }}” to “{{ $revision->title }}”.</p>
    @endif

    @if ($stats['added'] + $stats['removed'] === 0)
        <p class="lore-muted">The content didn't change.</p>
    @else
        <div class="lore-diff" role="table" aria-label="Line changes">
            @foreach ($diff as [$op, $line])
                <div class="{{ ['+' => 'add', '-' => 'del', ' ' => 'eq'][$op] }}">{{ $op }} {{ $line }}</div>
            @endforeach
        </div>
    @endif
@endsection
