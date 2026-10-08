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
use Ruvelo\Wiki\Wiki;

#[IsReadOnly]
class SearchPages extends Tool
{
    protected string $name = 'search_pages';

    protected string $title = 'Search pages';

    protected string $description = 'Search the wiki by words in page titles and text. Returns matching pages, title matches first, each with its slug, a snippet around the match and its URL. Use read_page with a slug to get the full text.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->description('Words to look for, e.g. "refund policy".')->required(),
            'limit' => $schema->integer()->min(1)->max(50)->default(10)->description('How many pages to return, at most 50.'),
        ];
    }

    public function handle(Request $request): ResponseFactory
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $query = trim($data['query']);
        $pages = Wiki::search($query)->limit($data['limit'] ?? 10)->get();

        return Response::structured([
            'query' => $query,
            'results' => $pages->map(fn (Page $page): array => Present::summary($page) + [
                'snippet' => Present::snippet($page, $query),
            ])->all(),
        ]);
    }
}
