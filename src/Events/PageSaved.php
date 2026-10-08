<?php

namespace FrancoisBultez\Lore\Events;

use FrancoisBultez\Lore\Models\Page;
use FrancoisBultez\Lore\Models\Revision;
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
