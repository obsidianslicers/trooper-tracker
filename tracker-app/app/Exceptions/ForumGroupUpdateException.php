<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a trooper's XenForo user groups could not be changed.
 */
class ForumGroupUpdateException extends RuntimeException {}
