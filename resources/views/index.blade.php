@extends('lore::layout')

@section('title', 'All pages')

@section('content')
    <div class="lore-head">
        <h1>All pages</h1>
        <span class="lore-muted">{{ number_format($pages->total()) }} {{ \Illuminate\Support\Str::plural('page', $pages->total()) }}</span>
    </div>

    @if ($pages->isEmpty())
        <p>The wiki is empty.
            @if ($canEdit)
                <a href="{{ route('lore.create', ['title' => \Illuminate\Support\Str::ucfirst(config('lore.home'))]) }}">Write the first page.</a>
            @endif
        </p>
    @else
        <ul class="lore-list">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ route('lore.show', $page) }}">{{ $page->title }}</a>
                    <small>edited {{ $page->updated_at->diffForHumans() }}</small>
                </li>
            @endforeach
        </ul>

        {{ $pages->links() }}
    @endif
@endsection
