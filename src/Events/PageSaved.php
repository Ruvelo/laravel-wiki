<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Models\Revision;

/**
 * Fired after a page is created, edited or restored.
 */
final class PageSaved
{
    use Dispatchable;

    public function __construct(
        public readonly Page $page,
        public readonly Revision $revision,
    ) {}

    public function wasCreated(): bool
    {
        return $this->revision->previous() === null;
    }
}
