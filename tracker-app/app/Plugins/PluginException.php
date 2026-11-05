<?php

declare(strict_types=1);

namespace App\Plugins;

use RuntimeException;

/**
 * A plugin could not be enabled, upgraded, disabled or uninstalled. Nothing was
 * left half done, and the message is safe to show to an administrator.
 */
abstract class PluginException extends RuntimeException {}
