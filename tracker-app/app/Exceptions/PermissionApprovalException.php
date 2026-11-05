<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Stops a permission request's approval, such as when a forum rejects a change.
 * The approval is rolled back and the message is shown to the administrator.
 */
class PermissionApprovalException extends RuntimeException {}
