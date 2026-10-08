<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Ruvelo\Wiki\Mcp\Present;
use Ruvelo\Wiki\Models\Revision;
use Ruvelo\Wiki\Wiki;

#[IsReadOnly]
class RecentChanges extends Tool
{
    protected string $name = 'recent_changes';

    protected string $title = 'Recent changes';

    protected string $description = 'The latest edits across the wiki, newest first: which page, the edit summary, who made it and when. Pass a page to see only its history.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()->min(1)->max(100)->default(20)->description('How many edits to return, at most 100.'),
            'page' => $schema->string()->description('Only edits to this page (slug or title).'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Revision::query()
            ->with(['page:id,title,slug', 'author'])
            ->select(['id', 'page_id', 'title', 'summary', 'user_id', 'created_at'])
            ->orderByDesc('id')
            ->limit($data['limit'] ?? 20);

        if (isset($data['page'])) {
            $page = Wiki::find($data['page']);

            if ($page === null) {
                return Response::error("There is no page called “{$data['page']}”.");
            }

            $query->where('page_id', $page->id);
        }

        return Response::structured([
            'changes' => $query->get()->map(fn (Revision $revision): array => Present::revision($revision))->all(),
        ]);
    }
}
