<?php

declare(strict_types=1);

namespace App\Messages\Translations\Queries;

use App\Plugins\PluginManager;
use Hyperdrive\Message;

/**
 * Where translation files and the code that uses them live: the core app plus
 * every discovered plugin, enabled or not.
 *
 * @method static array{lang_paths: array<string, string>, code_paths: array<int, string>} call()
 */
final class GetTranslationSources extends Message
{
    /**
     * @return array{lang_paths: array<string, string>, code_paths: array<int, string>}
     */
    public function handle(PluginManager $plugins): array
    {
        $lang_paths = ['' => lang_path()];
        $code_paths = [app_path(), resource_path('views'), resource_path('svelte'), resource_path('js')];

        foreach ($plugins->all() as $manifest)
        {
            $lang_paths[$manifest->slug] = $manifest->path.'/lang';
            $code_paths[] = $manifest->path.'/src';
            $code_paths[] = $manifest->path.'/resources';
        }

        return [
            'lang_paths' => $lang_paths,
            'code_paths' => array_values(array_filter($code_paths, 'is_dir')),
        ];
    }
}
