<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Ruvelo\Wiki\Exceptions\EditConflict;
use Ruvelo\Wiki\Exceptions\InvalidTitle;
use Ruvelo\Wiki\Exceptions\PageAlreadyExists;
use Ruvelo\Wiki\Markdown\Renderer;
use Ruvelo\Wiki\Models\Page;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        return $this->show(config('wiki.home', 'home'));
    }

    public function index(): Response
    {
        $pages = Page::query()
            ->orderBy('title')
            ->paginate(config('wiki.per_page', 50), ['id', 'title', 'slug', 'updated_at']);

        return response()->view('wiki::index', ['pages' => $pages]);
    }

    public function show(string $slug): Response
    {
        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            return response()->view('wiki::missing', [
                'slug' => $slug,
                'title' => Str::ucfirst(str_replace('-', ' ', $slug)),
            ], 404);
        }

        return response()->view('wiki::show', [
            'page' => $page,
            'html' => $page->html(),
            'author' => $page->revisions()->with('author')->first()?->authorName(),
            'backlinks' => $page->backlinks()->orderBy('title')->get(['id', 'title', 'slug']),
        ]);
    }

    public function create(Request $request): Response
    {
        return response()->view('wiki::edit', [
            'page' => null,
            'title' => (string) $request->query('title', ''),
            'body' => '',
            'base' => null,
            'preview' => '',
        ]);
    }

    public function store(Request $request, Renderer $renderer): Response|RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->input('action') === 'preview') {
            return $this->previewForm(null, $data, $renderer);
        }

        try {
            $page = Page::draft($data['title']);
        } catch (InvalidTitle|PageAlreadyExists $e) {
            throw ValidationException::withMessages(['title' => $e instanceof PageAlreadyExists ? 'A page with this title already exists.' : $e->getMessage()]);
        }

        $page->commit($data['title'], $data['body'], $data['summary'] ?? 'Created page', $request->user());

        return redirect()->route('wiki.show', $page)->with('wiki.status', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        return response()->view('wiki::edit', [
            'page' => $page,
            'title' => $page->title,
            'body' => $page->body,
            'base' => $page->currentRevisionId(),
            'preview' => $page->html(),
        ]);
    }

    /**
     * The editor's live preview: Markdown in, HTML fragment out.
     */
    public function preview(Request $request, Renderer $renderer): Response
    {
        $data = $request->validate(['body' => ['nullable', 'string']]);

        return response($renderer->render($data['body'] ?? ''))->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function update(Request $request, Page $page, Renderer $renderer): Response|RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->input('action') === 'preview') {
            return $this->previewForm($page, $data, $renderer);
        }

        if ($page->isUnchanged($data['title'], $data['body'])) {
            return redirect()->route('wiki.show', $page)->with('wiki.status', 'No changes to save.');
        }

        try {
            $page->commit($data['title'], $data['body'], $data['summary'] ?? null, $request->user(), $request->integer('base'));
        } catch (EditConflict) {
            // The form comes back with the editor's text intact.
            throw ValidationException::withMessages([
                'body' => 'Someone else edited this page while you were working on it. Copy your changes, reload, and apply them again.',
            ]);
        }

        return redirect()->route('wiki.show', $page)->with('wiki.status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('wiki.index')->with('wiki.status', "Deleted “{$page->title}”.");
    }

    /**
     * @return array{title: string, body: string, summary?: string|null}
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'summary' => ['nullable', 'string', 'max:255'],
        ]);

        $data['title'] = trim($data['title']);
        $data['body'] = str_replace("\r\n", "\n", $data['body'] ?? '');

        return $data;
    }

    /**
     * @param  array{title: string, body: string, summary?: string|null}  $data
     */
    private function previewForm(?Page $page, array $data, Renderer $renderer): Response
    {
        return response()->view('wiki::edit', [
            'page' => $page,
            'title' => $data['title'],
            'body' => $data['body'],
            'summary' => $data['summary'] ?? null,
            'base' => request()->input('base'),
            'preview' => $renderer->render($data['body']),
        ]);
    }
}
