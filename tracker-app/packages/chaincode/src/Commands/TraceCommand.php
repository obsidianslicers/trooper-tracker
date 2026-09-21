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
    protected $signature = 'chaincode:trace 
                            {--dir=app : Target directory relative to root} 
                            {--u|unused-only : Display only files without active references}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scan files for usage chains and reveal unreferenced code targets';

    public function handle(): int
    {
        $target_dir = base_path($this->option('dir'));
        $unused_only = $this->option('unused-only');

        if (!is_dir($target_dir))
        {
            $this->error("Target sector [{$target_dir}] does not exist.");
            return self::FAILURE;
        }

        // 1. Gather target PHP files
        $finder = (new Finder())->files()->in($target_dir)->name('*.php');
        $files = [];

        foreach ($finder as $file)
        {
            $relative_path = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getRealPath());

            $files[$relative_path] = [
                'full_path' => $file->getRealPath(),
                'class' => $this->getClassFromFile($file->getRealPath()),
                'referenced' => false,
            ];
        }

        // 2. Scan codebase for usage references
        $all_php_files = (new Finder())->files()->in(base_path())->name('*.php');

        foreach ($all_php_files as $file)
        {
            $content = file_get_contents($file->getRealPath());

            foreach ($files as $relative_path => &$data)
            {
                // Ignore self-references
                if ($file->getRealPath() === $data['full_path'])
                {
                    continue;
                }

                if ($data['class'] && str_contains($content, $data['class']))
                {
                    $data['referenced'] = true;
                }
            }
        }

        // 3. Standard Console Output
        $unused_count = 0;
        $total_count = count($files);

        $this->newLine();
        $this->info("=== CHAINCODE SCAN: Target Lineage Analysis ===");
        $this->newLine();

        foreach ($files as $relative_path => $data)
        {
            $is_unused = !$data['referenced'];

            if ($is_unused)
            {
                $unused_count++;
            }

            // Skip verified/active files when --unused-only flag is set
            if ($unused_only && !$is_unused)
            {
                continue;
            }

            if ($is_unused)
            {
                // Red text background for UNLINKED files
                $this->line("<error> [UNLINKED] </error> <fg=red>{$relative_path}</>");
            }
            else
            {
                // Dimmed gray/default output for VERIFIED files
                $this->line("<fg=gray> [VERIFIED]  {$relative_path}</>");
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

        return self::SUCCESS;
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