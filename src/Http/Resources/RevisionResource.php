<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Ruvelo\Wiki\Models\Revision;

/**
 * @mixin Revision
 */
final class RevisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'summary' => $this->summary,
            'author' => $this->whenLoaded('author', fn () => $this->authorName()),
            'created_at' => $this->created_at->toIso8601String(),
            'body' => $this->whenHas('body'),
        ];
    }
}
