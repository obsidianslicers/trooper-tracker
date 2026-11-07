<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Exceptions;

use RuntimeException;

/**
 * The other tracker couldn't be reached or sent back something unusable.
 */
class TrackerUnavailableException extends RuntimeException {}
