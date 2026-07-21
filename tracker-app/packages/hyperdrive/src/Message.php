<?php

declare(strict_types=1);

namespace Hyperdrive;

use Illuminate\Http\Request;

/**
 * Base transport message used by the message bus.
 *
 * Messages are hydrated from raw request payloads before they enter
 * validation, authorization, and handler execution pipelines.
 */
abstract class Message
{
    /**
     * Boot the command from request data and handle it, otherwise use the provided arguments.
     * @param mixed ...$args
     * @return mixed
     */
    public static function call(...$args): mixed
    {
        $request = null;
        $params = [];

        // Case A: Explicit Request instance passed as the first or only argument
        if (isset($args[0]) && $args[0] instanceof Request)
        {
            $request = $args[0];
            // Slice out the request if there are subsequent arguments following it
            $remaining_args = array_slice($args, 1);

            $params = (count($remaining_args) === 1 && is_array($remaining_args[0]))
                ? $remaining_args[0]
                : $remaining_args;
        }
        // Case B: Pure array or manual array parameter list mapping passed
        elseif (count($args) === 1 && is_array($args[0] ?? null))
        {
            $params = $args[0];
        }
        else
        {
            $params = $args;
        }

        return app(MessageDispatcher::class)->handle(static::class, $request, $params);
    }
}