@extends('wiki::layout')

@section('title', $page->title.' · revision #'.$revision->id)

@section('content')
    <div class="wiki-head">
        <h1><a href="{{ route('wiki.show', $page) }}">{{ $page->title }}</a> · #{{ $revision->id }}</h1>
        <div class="wiki-actions">
            <a href="{{ route('wiki.history', $page) }}">All revisions</a>
        </div>
    </div>

    <p class="wiki-meta">
        {{ $revision->authorName() ?? 'someone' }}, {{ $revision->created_at->toDayDateTimeString() }}
        @if ($revision->summary) · {{ $revision->summary }} @endif
        @if ($isCurrent) · <strong>current version</strong> @endif
    </p>

    @if ($canEdit && ! $isCurrent)
        <form method="post" action="{{ route('wiki.restore', [$page, $revision]) }}">
            @csrf
            <button type="submit">Restore this version</button>
        </form>
    @endif

    <h2>
        {{ $previous ? 'Changes since #'.$previous->id : 'First version' }}
        <small class="wiki-muted" style="font-size: .9rem; font-family: var(--wiki-sans)">
            <span class="wiki-add">+{{ $stats['added'] }}</span> <span class="wiki-del">−{{ $stats['removed'] }}</span>
        </small>
    </h2>

    @if ($previous && $previous->title !== $revision->title)
        <p>Renamed from “{{ $previous->title }}” to “{{ $revision->title }}”.</p>
    @endif

    @if ($stats['added'] + $stats['removed'] === 0)
        <p class="wiki-muted">The content didn't change.</p>
    @else
        <div class="wiki-diff" role="table" aria-label="Line changes">
            @foreach ($diff as [$op, $line])
                <div class="{{ ['+' => 'add', '-' => 'del', ' ' => 'eq'][$op] }}">{{ $op }} {{ $line }}</div>
            @endforeach
        </div>
    @endif
@endsection
