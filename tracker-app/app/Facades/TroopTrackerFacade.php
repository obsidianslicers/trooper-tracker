<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Facade for the TroopTracker service.
 *
 * @see TroopTracker
 */
class TroopTrackerFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return TroopTracker::class;
    }
}
