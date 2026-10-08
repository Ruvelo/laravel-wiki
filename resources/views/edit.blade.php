@extends('wiki::layout')

@section('title', $page ? 'Editing '.$page->title : 'New page')
@section('wide', true)

@section('content')
    <div class="wiki-head">
        <h1>{{ $page ? 'Editing '.$page->title : 'New page' }}</h1>
        @if ($page)
            <div class="wiki-actions">
                <a href="{{ route('wiki.show', $page) }}">Cancel</a>
            </div>
        @endif
    </div>

    <form method="post" action="{{ $page ? route('wiki.update', $page) : route('wiki.store') }}" class="wiki-editor">
        @csrf
        @if ($page)
            @method('put')
            <input type="hidden" name="base" value="{{ old('base', $base) }}">
        @endif

        <div>
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
                <button type="submit" name="action" value="preview" class="wiki-btn--quiet" data-wiki-preview-button>Preview</button>
            </div>
        </div>

        <section class="wiki-editor-preview" aria-label="Preview" aria-live="polite">
            <p class="wiki-muted"><small>Preview</small></p>
            <div class="wiki-prose" id="wiki-preview">{!! $preview !!}</div>
        </section>
    </form>

    @if ($page)
        <form method="post" action="{{ route('wiki.destroy', $page) }}" onsubmit="return confirm('Delete this page and its whole history?')" class="wiki-row" style="margin-top: 3rem">
            @csrf
            @method('delete')
            <button type="submit" class="wiki-btn--danger">Delete page</button>
        </form>
    @endif

    <script>
        // Live preview: re-render a moment after typing stops. Without
        // JavaScript the Preview button does the same with a round trip.
        (() => {
            const form = document.querySelector('.wiki-editor');
            const body = form.querySelector('textarea[name=body]');
            const pane = document.getElementById('wiki-preview');
            let timer, pending;

            form.querySelector('[data-wiki-preview-button]').hidden = true;

            body.addEventListener('input', () => {
                clearTimeout(timer);
                timer = setTimeout(async () => {
                    pending?.abort();
                    pending = new AbortController();
                    try {
                        const response = await fetch(@json(route('wiki.preview')), {
                            method: 'POST',
                            signal: pending.signal,
                            headers: { 'X-CSRF-TOKEN': form.querySelector('[name=_token]').value, 'Accept': 'text/html' },
                            body: new URLSearchParams({ body: body.value }),
                        });
                        if (response.ok) pane.innerHTML = await response.text();
                    } catch (error) {
                        if (error.name !== 'AbortError') throw error;
                    }
                }, 300);
            });
        })();
    </script>
@endsection
