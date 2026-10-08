<?php

namespace Ruvelo\Wiki\Models;

use Ruvelo\Wiki\Events\PageSaved;
use Ruvelo\Wiki\Markdown\Renderer;
use Ruvelo\Wiki\Support\WikiLinks;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Page extends Model
{
    protected $fillable = ['title', 'slug', 'body'];

    protected $attributes = ['body' => ''];

    protected static function booted(): void
    {
        // Don't rely on the database cascading: SQLite only does so with
        // foreign keys switched on.
        static::deleting(function (Page $page) {
            $page->revisions()->delete();
            $page->getConnection()->table(static::linksTable())->where('page_id', $page->id)->delete();
        });
    }

    public function getTable(): string
    {
        return config('wiki.table_prefix', 'wiki_').'pages';
    }

    public static function linksTable(): string
    {
        return config('wiki.table_prefix', 'wiki_').'links';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The slug a title (or a [[wiki link]] target) resolves to. Unicode
     * letters are kept, so non-Latin titles get readable slugs.
     */
    public static function slugFor(string $title): string
    {
        return Str::slug($title, '-', null);
    }

    /**
     * Newest first.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class)->orderByDesc('id');
    }

    /**
     * Pages whose body links to this one.
     */
    public function backlinks(): Builder
    {
        return static::query()->whereIn(
            'id',
            $this->getConnection()->table(static::linksTable())
                ->where('target_slug', $this->slug)
                ->select('page_id'),
        );
    }

    /**
     * The body without Markdown syntax, for search snippets and previews.
     */
    public function plainText(): string
    {
        $text = preg_replace([
            '/\[\[[^\]|]*\|([^\]]*)\]\]/u',   // [[Target|label]] -> label
            '/\[\[([^\]]*)\]\]/u',              // [[Target]] -> Target
            '/!?\[([^\]]*)\]\([^)]*\)/u',       // [text](url) -> text
            '/^\s{0,3}(#{1,6}|>|[-*+]\s+\[[ xX]\]|[-*+]|\d+\.)\s+/mu',
            '/^\s*(\|?\s*:?-{3,}:?\s*)+\|?\s*$/mu', // table rules
            '/^(`{3,}|~{3,}).*$/mu',
            '/\[TOC\]/u',
            '/\*{1,3}|`+/u',
            '/\|/u',
        ], ['$1', '$1', '$1', '', '', '', '', '', ' '], $this->body);

        return trim(preg_replace('/\s+/u', ' ', $text));
    }

    public function html(): string
    {
        return app(Renderer::class)->render($this->body);
    }

    /**
     * Save a new version of the page: updates it, records the revision and
     * re-indexes its outgoing links, all in one transaction.
     */
    public function commit(string $title, string $body, ?string $summary = null, ?Authenticatable $author = null): Revision
    {
        $revision = $this->getConnection()->transaction(function () use ($title, $body, $summary, $author) {
            $this->fill(['title' => $title, 'body' => $body])->save();

            $revision = $this->revisions()->create([
                'title' => $title,
                'body' => $body,
                'summary' => $summary,
                'user_id' => $author?->getAuthIdentifier(),
            ]);

            $links = $this->getConnection()->table(static::linksTable());
            $links->where('page_id', $this->id)->delete();
            $links->insert(array_map(
                fn (string $slug) => ['page_id' => $this->id, 'target_slug' => $slug],
                WikiLinks::targets($body),
            ));

            return $revision;
        });

        event(new PageSaved($this, $revision));

        return $revision;
    }
}
