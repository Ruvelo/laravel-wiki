@extends('lore::layout')

@section('title', $page ? 'Editing '.$page->title : 'New page')

@section('content')
    <div class="lore-head">
        <h1>{{ $page ? 'Editing '.$page->title : 'New page' }}</h1>
        @if ($page)
            <div class="lore-actions">
                <a href="{{ route('lore.show', $page) }}">Cancel</a>
            </div>
        @endif
    </div>

    @if ($preview !== null)
        <section class="lore-preview" aria-label="Preview">
            <p class="lore-muted"><small>Preview, not saved yet</small></p>
            <div class="lore-prose">{!! $preview !!}</div>
        </section>
    @endif

    <form method="post" action="{{ $page ? route('lore.update', $page) : route('lore.store') }}">
        @csrf
        @if ($page)
            @method('put')
            <input type="hidden" name="base" value="{{ old('base', $base) }}">
        @endif

        <label for="lore-title">Title</label>
        <input id="lore-title" type="text" name="title" value="{{ old('title', $title) }}" required maxlength="255" @unless($page) autofocus @endunless>
        @if ($page)
            <p class="lore-muted"><small>Renaming keeps the address: /{{ config('lore.path') }}/{{ $page->slug }}</small></p>
        @endif
        @error('title') <p class="lore-error">{{ $message }}</p> @enderror

        <label for="lore-body">Content</label>
        <textarea id="lore-body" name="body" spellcheck="true" @if($page) autofocus @endif>{{ old('body', $body) }}</textarea>
        @error('body') <p class="lore-error">{{ $message }}</p> @enderror

        <details class="lore-help">
            <summary>Formatting help</summary>
            <p>
                Markdown, GitHub-style: <code>## Heading</code>, <code>**bold**</code>, <code>*italic*</code>,
                <code>- list</code>, <code>- [ ] task</code>, tables and fenced code.
                Link to another page with <code>[[Page title]]</code> or <code>[[Page title|link text]]</code>.
                Links to pages that don't exist yet show in red. Put <code>[TOC]</code> on its own line for a table of contents.
            </p>
        </details>

        <label for="lore-summary">Summary of changes <span class="lore-muted">(optional)</span></label>
        <input id="lore-summary" type="text" name="summary" value="{{ old('summary', $summary ?? '') }}" maxlength="255" placeholder="Fixed a typo, added the deploy steps…">
        @error('summary') <p class="lore-error">{{ $message }}</p> @enderror

        <div class="lore-row">
            <button type="submit" name="action" value="save">{{ $page ? 'Save changes' : 'Create page' }}</button>
            <button type="submit" name="action" value="preview" class="lore-btn--quiet">Preview</button>
        </div>
    </form>

    @if ($page)
        <form method="post" action="{{ route('lore.destroy', $page) }}" onsubmit="return confirm('Delete this page and its whole history?')" class="lore-row" style="margin-top: 3rem">
            @csrf
            @method('delete')
            <button type="submit" class="lore-btn--danger">Delete page</button>
        </form>
    @endif
@endsection
