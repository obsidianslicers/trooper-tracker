<?php

declare(strict_types=1);

namespace App\Bus\Contracts;

use App\Bus\Concerns\ShouldRunAfterResponse;
use App\Bus\MagicBus;

/**
 * Interface for Command handlers.
 *
 * Command handlers process Command messages that represent actions or intentions
 * to change system state. Unlike Queries, Commands typically do not return values
 * (or return void/bool for success indication).
 *
 * Commands are used for:
 * - Creating, updating, or deleting resources
 * - Triggering business processes
 * - Sending notifications
 * - Any operation that changes system state
 *
 * Naming Convention:
 * - Command class: CreateUserCommand, UpdateProfileCommand
 * - Handler class: CreateUserCommandHandler, UpdateProfileCommandHandler
 *
 * Example:
 * ```php
 * class CreateUserCommandHandler implements CommandHandlerInterface
 * {
 *     public function __invoke(CreateUserCommand $command): void
 *     {
 *         User::create([
 *             'name' => $command->name,
 *             'email' => $command->email,
 *         ]);
 *     }
 * }
 * ```
 *
 * @template TMessage of object
 *
 * @extends HandlerInterface<TMessage>
 *
 * @see MagicBus
 * @see HandlerInterface
 * @see ShouldRunAfterResponse
 */
interface CommandHandlerInterface extends HandlerInterface {}
