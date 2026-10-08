<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Ruvelo\Wiki\Exceptions\EditConflict;
use Ruvelo\Wiki\Exceptions\InvalidTitle;
use Ruvelo\Wiki\Exceptions\PageAlreadyExists;
use Ruvelo\Wiki\Http\Resources\PageResource;
use Ruvelo\Wiki\Models\Page;

class PageController extends Controller
{
    /**
     * GET /pages?q=deploy
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = trim((string) $request->query('q', ''));

        $pages = Page::query()
            ->when($query !== '', fn ($pages) => $pages->matching($query), fn ($pages) => $pages->orderBy('title'))
            ->paginate(min($request->integer('per_page', config('wiki.per_page', 50)), 100))
            ->withQueryString();

        return PageResource::collection($pages);
    }

    /**
     * GET /pages/{slug}
     */
    public function show(Page $page): PageResource
    {
        return (new PageResource($page))->full();
    }

    /**
     * POST /pages {title, body?, summary?}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'summary' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $page = Page::draft(trim($data['title']));
        } catch (InvalidTitle $e) {
            return $this->error($e->getMessage(), 422, ['title' => [$e->getMessage()]]);
        } catch (PageAlreadyExists $e) {
            return $this->error($e->getMessage(), 409, ['page' => route('wiki.api.pages.show', $e->page)]);
        }

        $page->commit($page->title, $this->body($data['body'] ?? ''), $data['summary'] ?? 'Created page', $request->user());

        return (new PageResource($page))->full()->response()->setStatusCode(201);
    }

    /**
     * PATCH /pages/{slug} {title?, body?, summary?, base_revision?}
     *
     * Send base_revision (the "revision" you last read) to be refused with a
     * 409 instead of overwriting someone else's newer save.
     */
    public function update(Request $request, Page $page): PageResource|JsonResponse
    {
        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'body' => ['sometimes', 'nullable', 'string'],
            'summary' => ['nullable', 'string', 'max:255'],
            'base_revision' => ['nullable', 'integer'],
        ]);

        $title = trim($data['title'] ?? $page->title);
        $body = array_key_exists('body', $data) ? $this->body($data['body'] ?? '') : $page->body;

        if (! $page->isUnchanged($title, $body)) {
            try {
                $page->commit($title, $body, $data['summary'] ?? null, $request->user(), $data['base_revision'] ?? null);
            } catch (EditConflict $e) {
                return $this->error('The page changed since base_revision.', 409, ['current_revision' => $e->currentRevision]);
            }
        }

        return (new PageResource($page))->full();
    }

    /**
     * DELETE /pages/{slug}
     */
    public function destroy(Page $page): Response
    {
        $page->delete();

        return response()->noContent();
    }

    private function body(string $body): string
    {
        return str_replace("\r\n", "\n", $body);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function error(string $message, int $status, array $details = []): JsonResponse
    {
        return response()->json(['message' => $message, ...$status === 422 ? ['errors' => $details] : $details], $status);
    }
}
