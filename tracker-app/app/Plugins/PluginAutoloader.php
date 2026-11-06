<?php

declare(strict_types=1);

namespace App\Plugins;

/**
 * A minimal PSR-4 autoloader for plugins that live in the local plugins folder.
 *
 * Composer packages are autoloaded by Composer and never pass through here.
 */
class PluginAutoloader
{
    /**
     * @var array<string, string> Namespace prefix => source directory.
     */
    private array $prefixes = [];

    private bool $is_registered = false;

    public function add(string $namespace, string $source_path): void
    {
        $this->prefixes[trim($namespace, '\\').'\\'] = rtrim($source_path, '/').'/';

        if (!$this->is_registered)
        {
            spl_autoload_register($this->load(...));
            $this->is_registered = true;
        }
    }

    private function load(string $class): void
    {
        foreach ($this->prefixes as $prefix => $directory)
        {
            if (!str_starts_with($class, $prefix))
            {
                continue;
            }

            $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
            $file = $directory.$relative.'.php';

            if (is_file($file))
            {
                require_once $file;

                return;
            }
        }
    }
}
