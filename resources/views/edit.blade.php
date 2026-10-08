@extends('wiki::layout')

@section('title', $page ? 'Editing '.$page->title : 'New page')

@section('content')
    <div class="wiki-head">
        <h1>{{ $page ? 'Editing '.$page->title : 'New page' }}</h1>
        @if ($page)
            <div class="wiki-actions">
                <a href="{{ route('wiki.show', $page) }}">Cancel</a>
            </div>
        @endif
    </div>

    @if ($preview !== null)
        <section class="wiki-preview" aria-label="Preview">
            <p class="wiki-muted"><small>Preview, not saved yet</small></p>
            <div class="wiki-prose">{!! $preview !!}</div>
        </section>
    @endif

    <form method="post" action="{{ $page ? route('wiki.update', $page) : route('wiki.store') }}">
        @csrf
        @if ($page)
            @method('put')
            <input type="hidden" name="base" value="{{ old('base', $base) }}">
        @endif

        <label for="wiki-title">Title</label>
        <input id="wiki-title" type="text" name="title" value="{{ old('title', $title) }}" required maxlength="255" @unless($page) autofocus @endunless>
        @if ($page)
            <p class="wiki-muted"><small>Renaming keeps the address: /{{ config('wiki.path') }}/{{ $page->slug }}</small></p>
        @endif
        @error('title') <p class="wiki-error">{{ $message }}</p> @enderror

        <label for="wiki-body">Content</label>
        <textarea id="wiki-body" name="body" spellcheck="true" @if($page) autofocus @endif>{{ old('body', $body) }}</textarea>
        @error('body') <p class="wiki-error">{{ $message }}</p> @enderror

        <details class="wiki-help">
            <summary>Formatting help</summary>
            <p>
                Markdown, GitHub-style: <code>## Heading</code>, <code>**bold**</code>, <code>*italic*</code>,
                <code>- list</code>, <code>- [ ] task</code>, tables and fenced code.
                Link to another page with <code>[[Page title]]</code> or <code>[[Page title|link text]]</code>.
                Links to pages that don't exist yet show in red. Put <code>[TOC]</code> on its own line for a table of contents.
            </p>
        </details>

        <label for="wiki-summary">Summary of changes <span class="wiki-muted">(optional)</span></label>
        <input id="wiki-summary" type="text" name="summary" value="{{ old('summary', $summary ?? '') }}" maxlength="255" placeholder="Fixed a typo, added the deploy steps…">
        @error('summary') <p class="wiki-error">{{ $message }}</p> @enderror

        <div class="wiki-row">
            <button type="submit" name="action" value="save">{{ $page ? 'Save changes' : 'Create page' }}</button>
            <button type="submit" name="action" value="preview" class="wiki-btn--quiet">Preview</button>
        </div>
    </form>

    @if ($page)
        <form method="post" action="{{ route('wiki.destroy', $page) }}" onsubmit="return confirm('Delete this page and its whole history?')" class="wiki-row" style="margin-top: 3rem">
            @csrf
            @method('delete')
            <button type="submit" class="wiki-btn--danger">Delete page</button>
        </form>
    @endif
@endsection
