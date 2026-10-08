<?php

namespace FrancoisBultez\Lore\Http\Controllers;

use FrancoisBultez\Lore\Markdown\Renderer;
use FrancoisBultez\Lore\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class PageController extends Controller
{
    public function home(): Response
    {
        return $this->show(config('lore.home', 'home'));
    }

    public function index(): Response
    {
        $pages = Page::query()
            ->orderBy('title')
            ->paginate(config('lore.per_page', 50), ['id', 'title', 'slug', 'updated_at']);

        return response()->view('lore::index', ['pages' => $pages]);
    }

    public function show(string $slug): Response
    {
        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            return response()->view('lore::missing', [
                'slug' => $slug,
                'title' => Str::ucfirst(str_replace('-', ' ', $slug)),
            ], 404);
        }

        return response()->view('lore::show', [
            'page' => $page,
            'html' => $page->html(),
            'backlinks' => $page->backlinks()->orderBy('title')->get(['id', 'title', 'slug']),
        ]);
    }

    public function create(Request $request): Response
    {
        return response()->view('lore::edit', [
            'page' => null,
            'title' => (string) $request->query('title', ''),
            'body' => '',
            'base' => null,
            'preview' => null,
        ]);
    }

    public function store(Request $request, Renderer $renderer): Response|RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->input('action') === 'preview') {
            return $this->preview(null, $data, $renderer);
        }

        $slug = Page::slugFor($data['title']);

        if ($slug === '') {
            throw ValidationException::withMessages(['title' => 'The title needs at least one letter or number.']);
        }

        if (Page::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['title' => 'A page with this title already exists.']);
        }

        $page = new Page(['slug' => $slug]);
        $page->commit($data['title'], $data['body'], $data['summary'] ?? 'Created page', $request->user());

        return redirect()->route('lore.show', $page)->with('lore.status', 'Page created.');
    }

    public function edit(Page $page): Response
    {
        return response()->view('lore::edit', [
            'page' => $page,
            'title' => $page->title,
            'body' => $page->body,
            'base' => $page->revisions()->value('id'),
            'preview' => null,
        ]);
    }

    public function update(Request $request, Page $page, Renderer $renderer): Response|RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->input('action') === 'preview') {
            return $this->preview($page, $data, $renderer);
        }

        // Someone saved since this editor opened the page: don't silently
        // overwrite their work. The form comes back with the text intact.
        if ((int) $request->input('base') !== (int) $page->revisions()->value('id')) {
            throw ValidationException::withMessages([
                'body' => 'Someone else edited this page while you were working on it. Copy your changes, reload, and apply them again.',
            ]);
        }

        if ($data['title'] === $page->title && $data['body'] === $page->body) {
            return redirect()->route('lore.show', $page)->with('lore.status', 'No changes to save.');
        }

        $page->commit($data['title'], $data['body'], $data['summary'] ?? null, $request->user());

        return redirect()->route('lore.show', $page)->with('lore.status', 'Page saved.');
    }

    public function destroy(Page $page): RedirectResponse
    {
        $page->delete();

        return redirect()->route('lore.index')->with('lore.status', "Deleted “{$page->title}”.");
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

    private function preview(?Page $page, array $data, Renderer $renderer): Response
    {
        return response()->view('lore::edit', [
            'page' => $page,
            'title' => $data['title'],
            'body' => $data['body'],
            'summary' => $data['summary'] ?? null,
            'base' => request()->input('base'),
            'preview' => $renderer->render($data['body']),
        ]);
    }
}
