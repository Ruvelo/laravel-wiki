<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Exceptions;

final class InvalidTitle extends WikiException
{
    public function __construct(public readonly string $title)
    {
        parent::__construct('A page title needs at least one letter or number.');
    }
}
