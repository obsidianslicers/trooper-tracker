<?php

declare(strict_types=1);

namespace App\Bus\Concerns;

/**
 * Trait indicating that a command or query should be executed after the HTTP response is sent.
 *
 * This is useful for tasks that do not need to block the response, such as sending notifications
 * or performing background processing.
 */
trait ShouldRunAfterResponse
{
}