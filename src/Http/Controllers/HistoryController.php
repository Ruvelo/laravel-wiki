<?php

namespace Ruvelo\Wiki\Http\Controllers;

use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Models\Revision;
use Ruvelo\Wiki\Support\Diff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

class HistoryController extends Controller
{
    public function index(Page $page): Response
    {
        $revisions = $page->revisions()
            ->with('author')
            ->select(['id', 'page_id', 'title', 'summary', 'user_id', 'created_at'])
            ->paginate(config('wiki.per_page', 50));

        return response()->view('wiki::history', ['page' => $page, 'revisions' => $revisions]);
    }

    public function show(Page $page, Revision $revision): Response
    {
        $previous = $revision->previous();
        $diff = Diff::lines($previous->body ?? '', $revision->body);

        return response()->view('wiki::revision', [
            'page' => $page,
            'revision' => $revision,
            'previous' => $previous,
            'diff' => $diff,
            'stats' => Diff::stats($diff),
            'isCurrent' => $revision->id === $page->revisions()->value('id'),
        ]);
    }

    public function restore(Request $request, Page $page, Revision $revision): RedirectResponse
    {
        $page->commit($revision->title, $revision->body, "Restored revision #{$revision->id}", $request->user());

        return redirect()->route('wiki.show', $page)->with('wiki.status', "Restored revision #{$revision->id}.");
    }

    public function recent(): Response
    {
        $revisions = Revision::query()
            ->with(['page:id,title,slug', 'author'])
            ->select(['id', 'page_id', 'title', 'summary', 'user_id', 'created_at'])
            ->orderByDesc('id')
            ->paginate(config('wiki.per_page', 50));

        return response()->view('wiki::recent', ['revisions' => $revisions]);
    }
}
