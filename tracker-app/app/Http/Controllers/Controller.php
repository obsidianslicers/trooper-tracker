<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller providing message dispatching capabilities.
 *
 * This abstract controller serves as the foundation for all application controllers
 * that need to dispatch messages. It includes authorization capabilities and
 * integrates the HasMessageDispatcher trait.
 */
abstract class Controller
{
    use AuthorizesRequests;
}
