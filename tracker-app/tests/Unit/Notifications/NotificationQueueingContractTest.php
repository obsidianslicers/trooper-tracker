<?php

declare(strict_types=1);

namespace Tests\Unit\Notifications;

use App\Notifications\BaseNotification;
use App\Notifications\Tests\TestPushNotification;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Finder\Finder;

/**
 * Guards the decision that notification delivery fans out per recipient: every
 * notification must extend BaseNotification, which is where ShouldQueueAfterCommit
 * and the retry policy live. A notification that extends Illuminate's base
 * directly would send synchronously inside whatever job dispatched it, so a
 * single failed send would retry the whole fan-out loop.
 */
class NotificationQueueingContractTest extends TestCase
{
    /**
     * A manual diagnostic ping that must not depend on a running queue, so it
     * deliberately extends Notification directly.
     */
    private const EXEMPT = [
        TestPushNotification::class,
    ];

    public function test_every_notification_extends_the_queued_base(): void
    {
        $classes = $this->notificationClasses();

        // Guard against the scan silently finding nothing (e.g. a bad path).
        $this->assertGreaterThan(15, count($classes));

        $offenders = array_values(array_filter(
            $classes,
            fn (string $class): bool => ! is_subclass_of($class, BaseNotification::class),
        ));

        $this->assertSame(
            [],
            $offenders,
            'These notifications bypass BaseNotification and its per-recipient '
            .'queue + retry policy: '.implode(', ', $offenders),
        );
    }

    /**
     * @return list<class-string>
     */
    private function notificationClasses(): array
    {
        $classes = [];

        foreach (Finder::create()->files()->name('*.php')->in(dirname(__DIR__, 3).'/app/Notifications') as $file)
        {
            $class = 'App\\Notifications\\'.str_replace(
                ['/', '.php'],
                ['\\', ''],
                $file->getRelativePathname(),
            );

            if ($class === BaseNotification::class || in_array($class, self::EXEMPT, true))
            {
                continue;
            }

            if (! class_exists($class))
            {
                continue;
            }

            if ((new ReflectionClass($class))->isAbstract())
            {
                continue;
            }

            $classes[] = $class;
        }

        sort($classes);

        return $classes;
    }
}
