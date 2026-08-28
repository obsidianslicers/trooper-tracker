<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when Google Sheets cannot be reached after the client has exhausted
 * its automatic retries (e.g. a sustained 429/500/503 backend outage).
 *
 * Callers should treat this as "data temporarily unavailable" and skip the
 * current run rather than treating it as an empty sheet.
 */
class GoogleSheetsUnavailableException extends RuntimeException {}
