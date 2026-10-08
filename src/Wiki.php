<?php

declare(strict_types=1);

namespace Ruvelo\Wiki;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use League\CommonMark\Environment\Environment;
use Ruvelo\Wiki\Exceptions\InvalidTitle;
use Ruvelo\Wiki\Exceptions\PageAlreadyExists;
use Ruvelo\Wiki\Markdown\Renderer;
use Ruvelo\Wiki\Models\Page;

/**
 * The wiki's public PHP API.
 *
 *     Wiki::write('Release notes', $markdown, 'Imported from GitHub', $user);
 *     Wiki::find('Release notes')?->html();
 *     Wiki::search('deploy')->limit(5)->get();
 */
final class Wiki
{
    /** @var list<Closure(Environment): void> */
    private static array $markdownExtenders = [];

    /**
     * Find a page by its title or its slug.
     */
    public static function find(string $titleOrSlug): ?Page
    {
        $slug = Page::slugFor($titleOrSlug);

        return $slug === '' ? null : Page::query()->where('slug', $slug)->first();
    }

    /**
     * Create a page. Fails if one with the same title already exists.
     *
     * @throws InvalidTitle
     * @throws PageAlreadyExists
     */
    public static function create(string $title, string $body = '', ?string $summary = 'Created page', ?Authenticatable $author = null): Page
    {
        $page = Page::draft($title);
        $page->commit($title, $body, $summary, $author);

        return $page;
    }

    /**
     * Create the page, or save a new version of it. Saving identical content
     * again records nothing.
     *
     * @throws InvalidTitle
     */
    public static function write(string $title, string $body, ?string $summary = null, ?Authenticatable $author = null): Page
    {
        $page = self::find($title) ?? Page::draft($title);

        if (! $page->isUnchanged($title, $body)) {
            $page->commit($title, $body, $summary ?? ($page->exists ? null : 'Created page'), $author);
        }

        return $page;
    }

    /**
     * Pages whose title or body contains the text, title matches first.
     *
     * @return Builder<Page>
     */
    public static function search(string $text): Builder
    {
        return Page::query()->matching($text);
    }

    /**
     * Markdown to safe HTML, with [[wiki links]] resolved.
     */
    public static function render(string $markdown): string
    {
        return app(Renderer::class)->render($markdown);
    }

    /**
     * Customise the Markdown pipeline: add CommonMark extensions, parsers or
     * renderers. Call it from a service provider's boot method.
     *
     *     Wiki::extendMarkdown(fn (Environment $env) => $env->addExtension(new FootnoteExtension));
     *
     * @param  Closure(Environment): void  $callback
     */
    public static function extendMarkdown(Closure $callback): void
    {
        self::$markdownExtenders[] = $callback;
        app(Renderer::class)->reset();
    }

    /**
     * @internal
     *
     * @return list<Closure(Environment): void>
     */
    public static function markdownExtenders(): array
    {
        return self::$markdownExtenders;
    }

    /**
     * Whether the user may create, edit, delete and restore pages.
     *
     * Define a `wiki-edit` gate to decide; without one, any signed-in user can.
     */
    public static function canEdit(?Authenticatable $user = null): bool
    {
        $user ??= auth()->user();

        if (Gate::has('wiki-edit')) {
            return Gate::forUser($user)->allows('wiki-edit');
        }

        return $user !== null;
    }

    /**
     * @return class-string<Model>
     */
    public static function userModel(): string
    {
        return config('wiki.user_model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';
    }

    /**
     * Forget registered extensions. For tests and long-running workers.
     */
    public static function flush(): void
    {
        self::$markdownExtenders = [];
        app(Renderer::class)->reset();
    }
}
