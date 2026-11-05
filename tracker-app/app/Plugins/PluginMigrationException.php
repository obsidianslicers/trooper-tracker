<?php

declare(strict_types=1);

namespace App\Plugins;

use RuntimeException;

/**
 * A plugin's migrations failed. Whatever they ran was rolled back first.
 */
final class PluginMigrationException extends RuntimeException {}
