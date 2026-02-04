<?php

declare(strict_types=1);

namespace App\Bus\Concerns;

/**
 * Marker trait indicating that a handler should be executed within a database transaction.
 *
 * When applied to a handler, MagicBus will wrap the handler's execution in a database transaction.
 * This ensures that all database operations performed by the handler are atomic and can be rolled back
 * if an error occurs. This is useful for tasks that require consistency and integrity of data, such as:
 * - Creating or updating multiple related records
 * - Performing complex business logic that involves multiple database operations
 * - Ensuring that side effects only occur if the entire operation succeeds
 *
 * Example:
 * ```php
 * class CreateUserCommandHandler implements HandlerInterface
 * {
 *     use ShouldBeTransactional;
 *
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
 * @see \App\Bus\MagicBus::send()
 * @see \App\Bus\Contracts\CommandInterface
 */
trait ShouldBeTransactional {}
