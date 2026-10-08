@extends('lore::layout')

@section('title', $title)

@section('content')
    <div class="lore-head">
        <h1>{{ $title }}</h1>
    </div>

    <p>There is no page here yet.</p>

    @if ($canEdit)
        <p><a class="lore-btn" href="{{ route('lore.create', ['title' => $title]) }}">Create “{{ $title }}”</a></p>
    @endif

    <p class="lore-muted">
        Or <a href="{{ route('lore.search', ['q' => $title, 'all' => 1]) }}">search for “{{ $title }}”</a>,
        or browse <a href="{{ route('lore.index') }}">all pages</a>.
    </p>
@endsection
