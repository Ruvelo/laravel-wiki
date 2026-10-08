<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Exceptions;

use Ruvelo\Wiki\Models\Page;

final class PageAlreadyExists extends WikiException
{
    public function __construct(public readonly Page $page)
    {
        parent::__construct("A page with this title already exists: “{$page->title}” (/{$page->slug}).");
    }
}
