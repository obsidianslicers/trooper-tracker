<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Generators\FactoryGenerator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use SplFileInfo;

class GenerateFactoriesCommand extends Command
{
    protected $signature = 'tracker:generate-factories';

    protected $description = 'Generate factories for existing models';

    public function handle(): int
    {
        $directory = $this->resolveModelPath();

        if (!File::exists($directory))
        {
            $this->error("Path does not exist [$directory]");

            return self::FAILURE;
        }

        $generator = resolve(FactoryGenerator::class);

        $this->loadModels($directory)
            ->filter(function ($model)
            {
                $model = new ReflectionClass($model);

                return $model->isSubclassOf(Model::class) && !$model->isAbstract();
            })
            ->each(function ($model) use ($generator)
            {
                $factory = $generator->generate($model);

                if ($factory)
                {
                    $this->line('<info>Model factory created:</info> ' . $factory);
                }
                else
                {
                    $this->line('<error>Failed to create factory for model:</error> ' . $model);
                }
            });

        return self::SUCCESS;
    }

    protected function loadModels(string $directory): Collection
    {
        return collect(File::files($directory))->map(function (SplFileInfo $file)
        {
            if (!preg_match('/^namespace\s+([^;]+)/m', $file->getContents(), $matches))
            {
                return null;
            }

            return $matches[1] . '\\' . $file->getBasename('.php');
        })->filter();
    }

    protected function resolveModelPath(): string
    {
        if (File::isDirectory(app_path('Models')))
        {
            return app_path('Models');
        }

        return app_path();
    }
}
