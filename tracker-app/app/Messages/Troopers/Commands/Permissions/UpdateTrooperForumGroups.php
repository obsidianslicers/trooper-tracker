<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Permissions;

use App\Exceptions\ForumGroupUpdateException;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Hyperdrive\Message;

/**
 * Adds and removes secondary user groups on a trooper's linked XenForo account.
 *
 * Groups not named in either list are left untouched.
 *
 * @method static void call(Trooper $trooper, array $group_ids_to_add = [], array $group_ids_to_remove = [])
 */
final class UpdateTrooperForumGroups extends Message
{
    /**
     * @param  array<int, int>  $group_ids_to_add
     * @param  array<int, int>  $group_ids_to_remove
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $group_ids_to_add = [],
        private readonly array $group_ids_to_remove = [],
    ) {}

    /**
     * @throws ForumGroupUpdateException
     */
    public function handle(XenforoService $xenforo): void
    {
        if ($this->group_ids_to_add === [] && $this->group_ids_to_remove === [])
        {
            return;
        }

        $xenforo_user_id = $xenforo->resolve_user_id_for_trooper($this->trooper->id);

        if ($xenforo_user_id === null)
        {
            throw new ForumGroupUpdateException(
                "{$this->trooper->display_name} has no linked forum account."
            );
        }

        $current = $xenforo->getUserSecondaryGroupIds($xenforo_user_id);

        if ($current === null)
        {
            throw new ForumGroupUpdateException(
                'Could not read the forum account\'s current user groups.'
            );
        }

        $desired = collect($current)
            ->diff($this->group_ids_to_remove)
            ->merge($this->group_ids_to_add)
            ->map(fn (int|string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($desired->sort()->values()->all() === collect($current)->sort()->values()->all())
        {
            return;
        }

        $this->send($xenforo, $xenforo_user_id, $desired->all());
    }

    /**
     * @param  array<int, int>  $group_ids
     */
    private function send(XenforoService $xenforo, int $xenforo_user_id, array $group_ids): void
    {
        //  an empty array would be dropped from the form body, so send an empty
        //  value to clear every secondary group
        $payload = ['secondary_group_ids' => $group_ids === [] ? '' : $group_ids];

        $result = $xenforo->update_user($xenforo_user_id, $payload);

        if ($result['status'] < 200 || $result['status'] >= 300)
        {
            throw new ForumGroupUpdateException(
                "The forum rejected the user group change (HTTP {$result['status']})."
            );
        }
    }
}
