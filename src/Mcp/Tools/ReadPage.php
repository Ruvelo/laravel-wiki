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
use Ruvelo\Wiki\Wiki;

#[IsReadOnly]
class ReadPage extends Tool
{
    protected string $name = 'read_page';

    protected string $title = 'Read a page';

    protected string $description = 'Read one wiki page by its slug or title. Returns the Markdown body, title, URL, last update, current revision, the pages it links to and the pages that link to it. [[Double brackets]] in the body are links to other pages.';

    public function schema(JsonSchema $schema): array
    {
        return [
            'page' => $schema->string()->description('The page slug (e.g. "deploy-guide") or its title (e.g. "Deploy guide").')->required(),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $data = $request->validate(['page' => ['required', 'string', 'max:255']]);

        $page = Wiki::find($data['page']);

        if ($page === null) {
            return Response::error("There is no page called “{$data['page']}”. Use search_pages to find it.");
        }

        return Response::structured(Present::page($page));
    }
}
