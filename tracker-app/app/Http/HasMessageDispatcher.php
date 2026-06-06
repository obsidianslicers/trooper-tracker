<?php

namespace App\Http;

use App\Messages\Message;
use Illuminate\Http\Request;
use InvalidArgumentException;

trait HasMessageDispatcher
{
    /**
     * Hydrate a message from the request and execute its corresponding handler.
     */
    protected function dispatchMessage(Request $request, string $message_class): mixed
    {
        /** @var Message $message */
        $message = $message_class::fromRequest($request);

        $handler_class = $message_class . 'Handler';

        if (!class_exists($handler_class))
        {
            throw new InvalidArgumentException("Handler for message [{$message_class}] not found.");
        }

        $handler = app($handler_class);

        return $handler->handle($message);
    }
}