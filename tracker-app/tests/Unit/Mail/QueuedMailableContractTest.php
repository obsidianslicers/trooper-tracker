<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Mail\HasRetryPolicy;
use App\Plugins\PluginAutoloader;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Every queued mailable uses HasRetryPolicy, which holds it under the outgoing mail
 * budget (ThrottleOutgoingMail) and retries it by time while it waits. One without it
 * would send straight past the host's hourly cap.
 */
class QueuedMailableContractTest extends TestCase
{
    public function test_every_queued_mailable_uses_the_retry_policy(): void
    {
        $classes = $this->queuedMailableClasses();

        // Guard against the scan silently finding nothing (e.g. a bad path).
        $this->assertGreaterThan(30, count($classes));

        $offenders = array_values(array_filter(
            $classes,
            fn (string $class): bool => ! in_array(HasRetryPolicy::class, class_uses_recursive($class), true),
        ));

        $this->assertSame(
            [],
            $offenders,
            'These queued mailables bypass HasRetryPolicy and the outgoing mail budget: '.implode(', ', $offenders),
        );
    }

    /**
     * @return list<class-string>
     */
    private function queuedMailableClasses(): array
    {
        $app_path = dirname(__DIR__, 3);
        $classes = $this->queuedMailablesIn($app_path.'/app/Mail', 'App\\Mail\\');

        // Composer doesn't know about plugins, so their classes need the plugin autoloader.
        $autoloader = new PluginAutoloader;

        foreach (glob($app_path.'/plugins/*/plugin.json') as $manifest)
        {
            $namespace = json_decode(file_get_contents($manifest), true)['namespace'];
            $source_path = dirname($manifest).'/src';

            $autoloader->add($namespace, $source_path);

            if (is_dir($source_path.'/Mail'))
            {
                $classes = [
                    ...$classes,
                    ...$this->queuedMailablesIn($source_path.'/Mail', $namespace.'\\Mail\\'),
                ];
            }
        }

        sort($classes);

        return $classes;
    }

    /**
     * @return list<class-string>
     */
    private function queuedMailablesIn(string $directory, string $namespace): array
    {
        $classes = [];

        foreach (Finder::create()->files()->name('*.php')->in($directory) as $file)
        {
            $class = $namespace.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname(),
            );

            if (! class_exists($class))
            {
                continue;
            }

            $reflection = new ReflectionClass($class);

            if ($reflection->isAbstract() || ! $reflection->isSubclassOf(Mailable::class) || ! $reflection->implementsInterface(ShouldQueue::class))
            {
                continue;
            }

            $classes[] = $class;
        }

        return $classes;
    }
}
