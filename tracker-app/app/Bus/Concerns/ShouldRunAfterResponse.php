<?php

declare(strict_types=1);

namespace App\Bus\Concerns;

/**
 * Marker trait indicating that a handler should be executed after the HTTP response is sent.
 *
 * When applied to a handler, MagicBus will defer execution until after the HTTP response
 * has been sent to the client. This is useful for tasks that do not need to block the
 * response, such as:
 * - Sending email notifications
 * - Logging analytics events
 * - Clearing caches
 * - Triggering webhooks
 *
 * Important: Handlers with this trait must process messages implementing CommandInterface.
 * Queries cannot be deferred since they must return a result synchronously.
 *
 * Example:
 * ```php
 * class SendWelcomeEmailCommandHandler implements HandlerInterface
 * {
 *     use ShouldRunAfterResponse;
 *
 *     public function __invoke(SendWelcomeEmailCommand $command): void
 *     {
 *         Mail::to($command->email)->send(new WelcomeEmail());
 *     }
 * }
 * ```
 *
 * @see \App\Bus\MagicBus::send()
 * @see \App\Bus\Contracts\CommandInterface
 */
trait ShouldRunAfterResponse {}
