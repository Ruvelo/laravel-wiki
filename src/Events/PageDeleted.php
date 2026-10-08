<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Ruvelo\Wiki\Models\Page;

/**
 * Fired after a page and its history are deleted. The model is no longer in
 * the database, but its attributes are still readable.
 */
final class PageDeleted
{
    use Dispatchable;

    public function __construct(public readonly Page $page) {}
}
