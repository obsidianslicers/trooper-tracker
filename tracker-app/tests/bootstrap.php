<?php

declare(strict_types=1);

/*
 * Plugins in the local plugins folder keep their tests in plugins/<slug>/tests,
 * under "<plugin namespace>\Tests". Composer doesn't know those folders, so map
 * them here, along with each plugin's src, so tests can use plugin classes before
 * the plugin is loaded.
 */

require __DIR__.'/../vendor/autoload.php';

$plugin_namespaces = [];

foreach (glob(__DIR__.'/../plugins/*/plugin.json') ?: [] as $manifest_path)
{
    $manifest = json_decode((string) file_get_contents($manifest_path), true);
    $namespace = is_array($manifest) ? trim((string) ($manifest['namespace'] ?? ''), '\\') : '';

    if ($namespace !== '')
    {
        $plugin_directory = dirname($manifest_path);
        $plugin_namespaces[$namespace.'\\Tests\\'] = $plugin_directory.'/tests/';
        $plugin_namespaces[$namespace.'\\'] = $plugin_directory.'/src/';
    }
}

spl_autoload_register(function (string $class) use ($plugin_namespaces): void
{
    foreach ($plugin_namespaces as $prefix => $directory)
    {
        if (str_starts_with($class, $prefix))
        {
            $file = $directory.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';

            if (is_file($file))
            {
                require_once $file;
            }

            return;
        }
    }
});
