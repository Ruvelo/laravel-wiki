<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ruvelo\Wiki\Models\Page;

/**
 * @mixin Page
 */
final class PageResource extends JsonResource
{
    /** Include body, rendered HTML, revision and links (single-page responses). */
    private bool $full = false;

    public function full(): self
    {
        $this->full = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $page = [
            'title' => $this->title,
            'slug' => $this->slug,
            'url' => route('wiki.show', $this->resource),
            'updated_at' => $this->updated_at->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
        ];

        if (! $this->full) {
            return $page;
        }

        return $page + [
            'revision' => $this->currentRevisionId(),
            'body' => $this->body,
            'html' => $this->html(),
            'links' => $this->outgoingLinks(),
            'backlinks' => $this->backlinks()->orderBy('title')->pluck('slug')->all(),
        ];
    }
}
