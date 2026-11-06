<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a club's spreadsheet cannot be read, for example during a Google
 * Sheets outage. Callers treat it as "data temporarily unavailable" and skip
 * the run instead of mistaking it for an empty sheet.
 */
class SheetUnavailableException extends RuntimeException {}
