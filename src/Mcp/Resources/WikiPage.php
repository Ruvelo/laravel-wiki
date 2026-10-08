<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Mcp\Resources;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Completions\CompletionResponse;
use Laravel\Mcp\Server\Contracts\Completable;
use Laravel\Mcp\Server\Contracts\HasUriTemplate;
use Laravel\Mcp\Server\Resource;
use Laravel\Mcp\Support\UriTemplate;
use Ruvelo\Wiki\Models\Page;

/**
 * Every page as a resource, `wiki://pages/{slug}`, so clients that let
 * people attach resources (Claude Desktop, Cursor) can pull one in.
 */
class WikiPage extends Resource implements Completable, HasUriTemplate
{
    protected string $name = 'page';

    protected string $title = 'Wiki page';

    protected string $description = 'A wiki page as Markdown, by slug. [[Double brackets]] link to other pages.';

    protected string $mimeType = 'text/markdown';

    public function uriTemplate(): UriTemplate
    {
        return new UriTemplate('wiki://pages/{slug}');
    }

    public function handle(Request $request): Response
    {
        $slug = rawurldecode((string) $request->get('slug', ''));
        $page = Page::query()->where('slug', $slug)->first();

        if ($page === null) {
            return Response::error("There is no page with the slug “{$slug}”.");
        }

        return Response::text("# {$page->title}\n\n{$page->body}");
    }

    /**
     * Suggest slugs as the user types.
     *
     * @param  array<string, mixed>  $context
     */
    public function complete(string $argument, string $value, array $context): CompletionResponse
    {
        if ($argument !== 'slug') {
            return CompletionResponse::empty();
        }

        $term = strtr($value, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

        /** @var list<string> $slugs */
        $slugs = Page::query()
            ->whereRaw("slug like ? escape '!'", [$term])
            ->orderBy('slug')
            ->limit(100)
            ->pluck('slug')
            ->all();

        return CompletionResponse::result($slugs);
    }
}
