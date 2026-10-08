<?php

namespace Ruvelo\Wiki\Events;

use Ruvelo\Wiki\Models\Page;
use Ruvelo\Wiki\Models\Revision;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired after a page is created, edited or restored.
 */
class PageSaved
{
    use Dispatchable;

    public function __construct(
        public readonly Page $page,
        public readonly Revision $revision,
    ) {}
}
