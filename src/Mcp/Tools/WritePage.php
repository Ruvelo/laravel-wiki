<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsDestructive;
use Ruvelo\Wiki\Exceptions\EditConflict;
use Ruvelo\Wiki\Exceptions\InvalidTitle;
use Ruvelo\Wiki\Exceptions\PageAlreadyExists;
use Ruvelo\Wiki\Mcp\Present;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Wiki;

/**
 * Only offered when `wiki.mcp.allow_writes` is true, and then only to users
 * who pass the `wiki-edit` gate. Every save is a revision, so nothing an
 * agent writes is lost: it can be diffed and restored from the history.
 */
#[IsDestructive(false)]
class WritePage extends Tool
{
    protected string $name = 'write_page';

    protected string $title = 'Write a page';

    protected string $description = 'Create a wiki page, or replace the text of an existing one, with a short summary of the edit. Send the whole Markdown body, not a diff. When updating, pass the revision you got from read_page as base_revision: if someone has saved the page since, the write is refused so you can read it again and merge. Link to other pages with [[Page title]].';

    public function shouldRegister(): bool
    {
        return (bool) config('wiki.mcp.allow_writes', false);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->max(255)->description('The page title. An existing page with this title is updated; otherwise a new page is created.')->required(),
            'body' => $schema->string()->description('The full page text in Markdown.')->required(),
            'summary' => $schema->string()->max(255)->description('What changed and why, in a few words, e.g. "Add the EU refund window".')->required(),
            'base_revision' => $schema->integer()->description('The revision you based this edit on, from read_page. Leave it out only when creating a page.'),
            'slug' => $schema->string()->description('To rename an existing page, its current slug; the title then becomes its new title.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        $user = $request->user();

        if (! Wiki::canEdit($user)) {
            return Response::error($user === null
                ? 'Writing to the wiki needs a signed-in user.'
                : 'You don’t have permission to edit the wiki.');
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['present', 'nullable', 'string'],
            'summary' => ['required', 'string', 'max:255'],
            'base_revision' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $title = trim($data['title']);
        $body = str_replace("\r\n", "\n", $data['body'] ?? '');
        $basedOn = $data['base_revision'] ?? null;

        if (isset($data['slug'])) {
            $page = Page::query()->where('slug', $data['slug'])->first();

            if ($page === null) {
                return Response::error("There is no page with the slug “{$data['slug']}”.");
            }
        } else {
            $page = Wiki::find($title);
        }

        $before = $page?->currentRevisionId();

        // Agents must have read what they replace: no blind overwrites.
        if ($before !== null && $basedOn === null) {
            return Response::error("“{$page->title}” already exists (revision {$before}). Read it with read_page first, then send your full new text with base_revision {$before}.");
        }

        try {
            if ($page === null) {
                $page = Wiki::create($title, $body, $data['summary'], $user);
            } elseif (! $page->isUnchanged($title, $body)) {
                $page->commit($title, $body, $data['summary'], $user, $basedOn);
            }
        } catch (InvalidTitle $e) {
            return Response::error($e->getMessage());
        } catch (PageAlreadyExists $e) {
            return Response::error("“{$e->page->title}” was created by someone else just now. Read it with read_page, then retry with base_revision.");
        } catch (EditConflict $e) {
            return Response::error("“{$e->page->title}” was changed by someone else: it is at revision {$e->currentRevision}, not {$e->expectedRevision}. Nothing was saved. Read the page again, merge your changes into its new text, and retry with base_revision {$e->currentRevision}.");
        }

        $revision = $page->currentRevisionId();

        return Response::structured([
            'created' => $before === null,
            'changed' => $revision !== $before,
            'page' => Present::summary($page),
            'revision' => $revision,
        ]);
    }
}
