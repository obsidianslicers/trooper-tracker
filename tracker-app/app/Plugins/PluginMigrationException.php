<?php

declare(strict_types=1);

namespace App\Plugins;

/**
 * A plugin's migrations failed. Whatever they ran was rolled back first.
 */
final class PluginMigrationException extends PluginException {}
