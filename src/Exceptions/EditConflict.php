<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Exceptions;

use Ruvelo\Wiki\Models\Page;

/**
 * The page was saved by someone else after the editor loaded it.
 */
final class EditConflict extends WikiException
{
    public function __construct(
        public readonly Page $page,
        public readonly ?int $expectedRevision,
        public readonly ?int $currentRevision,
    ) {
        parent::__construct("“{$page->title}” changed since revision #{$expectedRevision}; it is now at #{$currentRevision}.");
    }
}
