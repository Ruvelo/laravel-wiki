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
use Ruvelo\Wiki\Models\Page;

#[IsReadOnly]
class ListPages extends Tool
{
    protected string $name = 'list_pages';

    protected string $title = 'List pages';

    protected string $description = 'List every wiki page alphabetically, a page of results at a time. Returns titles, slugs and URLs; ask for the next page while has_more is true.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->integer()->min(1)->default(1)->description('Which page of results, starting at 1.'),
            'per_page' => $schema->integer()->min(1)->max(100)->default(50)->description('Results per page, at most 100.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        $data = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $pages = Page::query()
            ->orderBy('title')
            ->paginate($data['per_page'] ?? 50, ['id', 'title', 'slug', 'updated_at'], 'page', $data['page'] ?? 1);

        return Response::structured([
            'pages' => $pages->getCollection()->map(fn (Page $page): array => Present::summary($page))->all(),
            'page' => $pages->currentPage(),
            'per_page' => $pages->perPage(),
            'total' => $pages->total(),
            'has_more' => $pages->hasMorePages(),
        ]);
    }
}
