<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use Illuminate\Http\UploadedFile;
use ZipArchive;

trait BuildsPluginArchives
{
    /**
     * @param  array<string, string>  $files  Path inside the zip => contents.
     */
    protected function pluginArchive(array $files, string $name = 'plugin.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'plugin').'.zip';
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($files as $file => $contents)
        {
            $zip->addFromString($file, $contents);
        }

        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }

    /**
     * @return array<string, string>
     */
    protected function greeterFiles(string $prefix = 'greeter/', string $slug = 'greeter'): array
    {
        $manifest = json_encode([
            'slug' => $slug,
            'name' => 'Greeter',
            'version' => '1.0.0',
            'namespace' => 'GreeterPlugin',
            'provider' => 'GreeterPlugin\\GreeterServiceProvider',
        ]);

        return [
            $prefix.'plugin.json' => (string) $manifest,
            $prefix.'src/GreeterServiceProvider.php' => "<?php\n\nnamespace GreeterPlugin;\n\n"
                ."class GreeterServiceProvider extends \\App\\Plugins\\PluginServiceProvider {}\n",
        ];
    }
}
