<?php

namespace Ruvelo\Wiki\Models;

use Ruvelo\Wiki\Wiki;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $page_id
 * @property string $title
 * @property string $body
 * @property string|null $summary
 * @property string|null $user_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class Revision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['title', 'body', 'summary', 'user_id'];

    public function getTable(): string
    {
        return config('wiki.table_prefix', 'wiki_').'revisions';
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Wiki::userModel(), 'user_id');
    }

    public function authorName(): ?string
    {
        return $this->author?->getAttribute(config('wiki.user_name_attribute', 'name'));
    }

    /**
     * The revision this one replaced, if any.
     */
    public function previous(): ?self
    {
        return static::query()
            ->where('page_id', $this->page_id)
            ->where('id', '<', $this->id)
            ->orderByDesc('id')
            ->first();
    }
}
