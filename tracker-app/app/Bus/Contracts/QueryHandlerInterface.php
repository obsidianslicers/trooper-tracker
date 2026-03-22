<?php

declare(strict_types=1);

namespace App\Bus\Contracts;

use App\Bus\MagicBus;

/**
 * Interface for Query handlers.
 *
 * Query handlers process Query messages that represent requests for information
 * without changing system state. Queries always return data and must be executed
 * synchronously (they cannot use ShouldRunAfterResponse).
 *
 * Queries are used for:
 * - Retrieving single resources or collections
 * - Searching and filtering data
 * - Computing derived values
 * - Any read-only operation
 *
 * Naming Convention:
 * - Query class: GetUserQuery, GetTroopersForPickerQuery
 * - Handler class: GetUserQueryHandler, GetTroopersForPickerQueryHandler
 *
 * Example:
 * ```php
 * class GetTroopersForPickerQueryHandler implements QueryHandlerInterface
 * {
 *     public function __invoke(GetTroopersForPickerQuery $query): Collection
 *     {
 *         return Trooper::active()
 *             ->orderBy('name')
 *             ->get();
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
 */
interface QueryHandlerInterface extends HandlerInterface {}
