<?php

declare(strict_types=1);

namespace Chaincode\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

class TraceCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chaincode:trace';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan files for usage chains and reveal unreferenced code targets';

    public function handle(): int
    {
        $source_dir = base_path('app');
        $route_dir = base_path('routes');

        $target_dirs = [$source_dir, $route_dir];

        foreach ($target_dirs as $target_dir)
        {
            if (!is_dir($target_dir))
            {
                $this->error("Target sector [{$target_dir}] does not exist.");
                return self::FAILURE;
            }
        }

        // 1. Gather source PHP files
        $source_files = $this->getSourceFiles($source_dir);

        // 2. Scan codebase for usage references
        $application_files = $this->getApplicationFiles($target_dirs);

        $this->info("Gathered all PHP files for scanning = " . iterator_count($application_files));

        $this->analyzeSourceFiles($source_files, $application_files);
        $this->showSourceFileResults($source_files);

        return self::SUCCESS;
    }

    private function getApplicationFiles(array $target_dirs): iterable
    {
        $application_files = (new Finder())->files()->in($target_dirs)->name('*.php');

        $specific_files = [
            base_path('bootstrap/app.php'),
            base_path('bootstrap/providers.php'),
        ];

        foreach ($specific_files as $file_path)
        {
            if (file_exists($file_path))
            {
                $application_files->append([$file_path]);
            }
        }

        return $application_files;
    }

    private function analyzeSourceFiles(array &$source_files, iterable $application_files): void
    {
        foreach ($application_files as $file)
        {
            $content = file_get_contents($file->getRealPath());

            foreach ($source_files as $relative_path => &$data)
            {
                // Ignore self-references
                if ($file->getRealPath() === $data['full_path'])
                {
                    continue;
                }

                if ($data['class'] && preg_match('/\b' . preg_quote($data['class'], '/') . '\b/', $content))
                {
                    $data['referenced'] = true;
                }
            }
        }
    }

    private function showSourceFileResults(array $source_files): void
    {
        // 3. Standard Console Output
        $unused_count = 0;
        $total_count = count($source_files);

        $this->newLine();
        $this->info("=== CHAINCODE SCAN: Target Lineage Analysis ===");
        $this->newLine();

        foreach ($source_files as $relative_path => $data)
        {
            $is_used = $data['referenced'];

            if (!$is_used)
            {
                $unused_count++;
            }

            // Skip verified/active files when --unused-only flag is set
            if ($is_used)
            {
                continue;
            }

            if (!$is_used)
            {
                // Red text background for UNLINKED files
                $this->line("<error> [UNLINKED] </error> <fg=red>{$relative_path}</>");
            }
        }

        // Summary footer
        $this->newLine();
        $this->line("-----------------------------------------------");
        $this->line("Targets Scanned: <comment>{$total_count}</comment>");

        if ($unused_count > 0)
        {
            $this->line("Unreferenced Files: <fg=red;options=bold>{$unused_count}</>");
        }
        else
        {
            $this->info("Unreferenced Files: 0 (All files verified)");
        }
        $this->newLine();
    }

    private function getSourceFiles(string $source_dir): array
    {
        $finder = (new Finder())->files()->in($source_dir)->name('*.php');

        $source_files = [];

        foreach ($finder as $file)
        {
            $relative_path = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());

            $class_name = $this->getClassFromFile($file->getRealPath());

            if ($class_name)
            {
                $source_files[$relative_path] = [
                    'full_path' => $file->getRealPath(),
                    'class' => $class_name,
                    'referenced' => false,
                ];
            }
        }

        $this->info("Gathered source PHP files = " . count($source_files));

        return $source_files;
    }

    /**
     * Helper to extract class or interface name from a PHP file.
     */
    protected function getClassFromFile(string $path): ?string
    {
        $content = file_get_contents($path);

        if (preg_match('/namespace\s+(.+?);/', $content) &&
            preg_match('/(?:class|trait|interface|enum)\s+(\w+)/', $content, $class_match))
        {
            return $class_match[1];
        }

        return null;
    }
}