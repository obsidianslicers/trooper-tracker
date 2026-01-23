<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use RuntimeException;

/**
 * Provides automatic observer registration for models.
 *
 * This trait automatically registers a model observer class based on the model's
 * class name. It expects an observer class to exist at App\Models\Observers\{ModelName}Observer.
 * Throws a RuntimeException if the expected observer class is not found.
 */
trait HasObserver
{
    /**
     * Boot the HasObserver trait for a model.
     *
     * Automatically detects and registers an observer class based on the model's
     * class name. For example, a Trooper model will look for TrooperObserver.
     * Throws RuntimeException if the observer class doesn't exist.
     *
     * @return void
     * @throws RuntimeException If the expected observer class is not found.
     */
    public static function bootHasObserver()
    {
        $name = Str::of(static::class)
            ->classBasename()
            ->value();

        $observer_class = "App\\Models\\Observers\\{$name}Observer";

        if (class_exists($observer_class))
        {
            static::observe($observer_class);
        }
        else
        {
            // Fail loudly so you know the observer wasn't found
            throw new RuntimeException(
                sprintf('Observer class [%s] not found for model [%s]', $observer_class, static::class)
            );
        }

    }
}
