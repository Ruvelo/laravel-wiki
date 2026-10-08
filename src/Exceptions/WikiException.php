<?php

declare(strict_types=1);

namespace Ruvelo\Wiki\Exceptions;

use RuntimeException;

/**
 * Base class for the wiki's own exceptions, so callers can catch them all.
 */
abstract class WikiException extends RuntimeException {}
