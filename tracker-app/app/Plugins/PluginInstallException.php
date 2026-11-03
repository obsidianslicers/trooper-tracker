<?php

declare(strict_types=1);

namespace App\Plugins;

use RuntimeException;

/**
 * An uploaded plugin archive was refused. The message is safe to show to an administrator.
 */
final class PluginInstallException extends RuntimeException {}
