<?php

declare(strict_types=1);

namespace App\Messages\Plugins\Commands;

use Hyperdrive\Message;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Writes a starter plugin into the local plugins folder from stubs/plugin.
 *
 * @method static string call(string $name, ?string $namespace = null, bool $with_composer = false)
 */
final class ScaffoldPlugin extends Message
{
    private const FILES = [
        'plugin.json.stub' => 'plugin.json',
        'README.md.stub' => 'README.md',
        'ServiceProvider.php.stub' => 'src/{{ class }}ServiceProvider.php',
        'Listener.php.stub' => 'src/Listeners/LogPostedEvent.php',
        'config.php.stub' => 'config/config.php',
        'routes.php.stub' => 'routes/web.php',
        'event-panel.blade.php.stub' => 'resources/views/event-panel.blade.php',
    ];

    public function __construct(
        private readonly string $name,
        private readonly ?string $namespace = null,
        private readonly bool $with_composer = false,
    ) {}

    /**
     * @return string The new plugin's folder.
     */
    public function handle(Filesystem $files): string
    {
        $tokens = $this->tokens();
        $plugins_path = rtrim((string) config('plugins.path', base_path('plugins')), '/');
        $path = $plugins_path.'/'.$tokens['{{ slug }}'];

        if ($files->exists($path))
        {
            throw new InvalidArgumentException("A plugin already exists at [{$path}].");
        }

        $composer_stub = $this->with_composer ? ['composer.json.stub' => 'composer.json'] : [];
        $stubs = self::FILES + $composer_stub;

        foreach ($stubs as $stub => $target)
        {
            $destination = $path.'/'.strtr($target, $tokens);
            $files->ensureDirectoryExists(dirname($destination));
            $contents = $files->get(base_path('stubs/plugin/'.$stub));
            $files->put($destination, strtr($contents, $tokens));
        }

        $files->ensureDirectoryExists($path.'/database/migrations');
        $files->put($path.'/database/migrations/.gitkeep', '');

        return $path;
    }

    private function namespace(string $class): string
    {
        $namespace = trim($this->namespace ?? 'TroopTracker\\Plugins\\'.$class, '\\');

        if (preg_match('/^[A-Za-z_][A-Za-z0-9_]*(\\\\[A-Za-z_][A-Za-z0-9_]*)*$/', $namespace) !== 1)
        {
            throw new InvalidArgumentException("[{$namespace}] is not a valid PHP namespace.");
        }

        return $namespace;
    }

    /**
     * @return array<string, string>
     */
    private function tokens(): array
    {
        $slug = Str::slug($this->name);

        if ($slug === '')
        {
            throw new InvalidArgumentException(
                'The plugin name needs at least one letter or number.'
            );
        }

        $class = Str::studly($slug);
        $namespace = $this->namespace($class);
        $name = trim($this->name);

        return [
            '{{ slug }}' => $slug,
            '{{ name }}' => $name,
            '{{ name_json }}' => substr((string) json_encode($name, JSON_UNESCAPED_UNICODE), 1, -1),
            '{{ class }}' => $class,
            '{{ namespace }}' => $namespace,
            '{{ namespace_json }}' => str_replace('\\', '\\\\', $namespace),
            '{{ composer_name }}' => 'trooptracker/'.$slug,
        ];
    }
}
