<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Exceptions;

use RuntimeException;

/**
 * The other tracker no longer accepts the link's access key.
 */
class LinkRevokedException extends RuntimeException {}
