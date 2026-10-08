@extends('wiki::layout')

@section('title', $title)

@section('content')
    <div class="wiki-head">
        <h1>{{ $title }}</h1>
    </div>

    <p>There is no page here yet.</p>

    @if ($canEdit)
        <p><a class="wiki-btn" href="{{ route('wiki.create', ['title' => $title]) }}">Create “{{ $title }}”</a></p>
    @endif

    <p class="wiki-muted">
        Or <a href="{{ route('wiki.search', ['q' => $title, 'all' => 1]) }}">search for “{{ $title }}”</a>,
        or browse <a href="{{ route('wiki.index') }}">all pages</a>.
    </p>
@endsection
