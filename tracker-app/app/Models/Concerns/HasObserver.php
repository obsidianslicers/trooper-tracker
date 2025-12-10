<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Support\Str;
use RuntimeException;

trait HasObserver
{
    /**
     * Bootstrap the trait.
     *
     * @return void
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
