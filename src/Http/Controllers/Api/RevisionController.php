<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;
use Ruvelo\Wiki\Http\Resources\PageResource;
use Ruvelo\Wiki\Http\Resources\RevisionResource;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Models\Revision;

class RevisionController extends Controller
{
    /**
     * GET /pages/{slug}/revisions, newest first, without bodies.
     */
    public function index(Request $request, Page $page): AnonymousResourceCollection
    {
        $revisions = $page->revisions()
            ->with('author')
            ->select(['id', 'page_id', 'title', 'summary', 'user_id', 'created_at'])
            ->paginate(min($request->integer('per_page', config('wiki.per_page', 50)), 100));

        return RevisionResource::collection($revisions);
    }

    /**
     * GET /pages/{slug}/revisions/{id}, with the body as it was.
     */
    public function show(Page $page, Revision $revision): RevisionResource
    {
        return new RevisionResource($revision->load('author'));
    }

    /**
     * POST /pages/{slug}/revisions/{id}/restore
     */
    public function restore(Request $request, Page $page, Revision $revision): PageResource
    {
        $page->commit($revision->title, $revision->body, "Restored revision #{$revision->id}", $request->user());

        return (new PageResource($page))->full();
    }
}
