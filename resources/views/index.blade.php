@extends('wiki::layout')

@section('title', 'All pages')

@section('content')
    <div class="wiki-head">
        <h1>All pages</h1>
        <span class="wiki-muted">{{ number_format($pages->total()) }} {{ \Illuminate\Support\Str::plural('page', $pages->total()) }}</span>
    </div>

    @if ($pages->isEmpty())
        <p>The wiki is empty.
            @if ($canEdit)
                <a href="{{ route('wiki.create', ['title' => \Illuminate\Support\Str::ucfirst(config('wiki.home'))]) }}">Write the first page.</a>
            @endif
        </p>
    @else
        <ul class="wiki-list">
            @foreach ($pages as $page)
                <li>
                    <a href="{{ route('wiki.show', $page) }}">{{ $page->title }}</a>
                    <small>edited {{ $page->updated_at->diffForHumans() }}</small>
                </li>
            @endforeach
        </ul>

        {{ $pages->links() }}
    @endif
@endsection
