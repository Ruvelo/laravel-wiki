<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Models;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Ruvelo\Wiki\Database\Factories\PageFactory;
use Ruvelo\Wiki\Events\PageDeleted;
use Ruvelo\Wiki\Events\PageSaved;
use Ruvelo\Wiki\Exceptions\EditConflict;
use Ruvelo\Wiki\Exceptions\InvalidTitle;
use Ruvelo\Wiki\Exceptions\PageAlreadyExists;
use Ruvelo\Wiki\Markdown\Renderer;
use Ruvelo\Wiki\Support\Navigation;
use Ruvelo\Wiki\Support\WikiLinks;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property string $body
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;

    protected $fillable = ['title', 'slug', 'body'];

    protected $attributes = ['body' => ''];

    protected static function booted(): void
    {
        // Don't rely on the database cascading: SQLite only does so with
        // foreign keys switched on.
        static::deleting(function (Page $page): void {
            $page->revisions()->delete();
            $page->getConnection()->table(static::linksTable())->where('page_id', $page->id)->delete();
        });

        static::deleted(fn (Page $page) => event(new PageDeleted($page)));
    }

    protected static function newFactory(): PageFactory
    {
        return PageFactory::new();
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
     * Start a new page. Nothing is saved until you commit() it.
     *
     * @throws InvalidTitle
     * @throws PageAlreadyExists
     */
    public static function draft(string $title): self
    {
        $slug = static::slugFor($title);

        if ($slug === '') {
            throw new InvalidTitle($title);
        }

        $existing = static::query()->where('slug', $slug)->first();
        if ($existing !== null) {
            throw new PageAlreadyExists($existing);
        }

        return new self(['title' => $title, 'slug' => $slug]);
    }

    /**
     * Newest first.
     *
     * @return HasMany<Revision, $this>
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(Revision::class)->orderByDesc('id');
    }

    public function currentRevisionId(): ?int
    {
        $id = $this->revisions()->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Pages whose body links to this one.
     *
     * @return Builder<static>
     */
    public function backlinks(): Builder
    {
        return static::query()
            ->whereIn('id', $this->getConnection()->table(static::linksTable())
                ->where('target_slug', $this->slug)
                ->select('page_id'))
            // The menu links to everything; listing it would be noise.
            ->when(Navigation::sidebarSlug(), fn (Builder $query, string $slug) => $query->where('slug', '!=', $slug));
    }

    /**
     * Slugs of the pages this one links to, whether they exist or not.
     *
     * @return list<string>
     */
    public function outgoingLinks(): array
    {
        $slugs = $this->getConnection()->table(static::linksTable())
            ->where('page_id', $this->id)
            ->orderBy('target_slug')
            ->pluck('target_slug')
            ->all();

        return array_values(array_filter($slugs, is_string(...)));
    }

    /**
     * Pages whose title or body contains the text, title matches first.
     *
     * @param  Builder<static>  $query
     */
    public function scopeMatching(Builder $query, string $text): void
    {
        // "!" rather than backslash: SQLite has no default escape character
        // and MySQL treats backslash specially, but "!" means the same on all.
        $term = '%'.strtr($text, ['!' => '!!', '%' => '!%', '_' => '!_']).'%';

        $query->where(fn (Builder $where) => $where->whereRaw("title like ? escape '!'", [$term])->orWhereRaw("body like ? escape '!'", [$term]))
            ->orderByRaw("case when title like ? escape '!' then 0 else 1 end", [$term])
            ->orderBy('title');
    }

    public function html(): string
    {
        return app(Renderer::class)->render($this->body);
    }

    /**
     * The body without Markdown syntax, for search snippets and previews.
     */
    public function plainText(): string
    {
        $text = preg_replace([
            '/\[\[[^\]|]*\|([^\]]*)\]\]/u',          // [[Target|label]] -> label
            '/\[\[([^\]#]*)(#[^\]]*)?\]\]/u',        // [[Target#Section]] -> Target
            '/!?\[([^\]]*)\]\([^)]*\)/u',            // [text](url) -> text
            '/^\s{0,3}(#{1,6}|>|[-*+]\s+\[[ xX]\]|[-*+]|\d+\.)\s+/mu',
            '/^\s*(\|?\s*:?-{3,}:?\s*)+\|?\s*$/mu',  // table rules
            '/^(`{3,}|~{3,}).*$/mu',
            '/\[TOC\]/u',
            '/\*{1,3}|`+/u',
            '/\|/u',
        ], ['$1', '$1', '$1', '', '', '', '', '', ' '], $this->body) ?? $this->body;

        return trim(preg_replace('/\s+/u', ' ', $text) ?? $text);
    }

    public function isUnchanged(string $title, string $body): bool
    {
        return $this->exists && $title === $this->title && $body === $this->body;
    }

    /**
     * Save a new version: updates the page, records the revision and
     * re-indexes its links, in one transaction.
     *
     * Pass the revision the editor started from as $basedOn to refuse the
     * save if someone else has saved since.
     *
     * @throws EditConflict
     */
    public function commit(string $title, string $body, ?string $summary = null, ?Authenticatable $author = null, ?int $basedOn = null): Revision
    {
        $revision = $this->getConnection()->transaction(function () use ($title, $body, $summary, $author, $basedOn): Revision {
            if ($this->exists) {
                // Serialise concurrent saves of the same page, so the
                // conflict check below can't be raced.
                static::query()->whereKey($this->getKey())->lockForUpdate()->first(['id']);
            }

            if ($basedOn !== null && $this->exists && $basedOn !== ($current = $this->currentRevisionId())) {
                throw new EditConflict($this, $basedOn, $current);
            }

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
