<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Permissions;

use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Lists the organizations a requester may ask moderator rights for.
 *
 * Administrators see every organization; moderators only see the
 * organizations (and their descendants) they moderate.
 *
 * @method static Collection<int, Organization> call(Trooper $requester)
 */
final class GetRequestableOrganizations extends Message
{
    public function __construct(private readonly Trooper $requester) {}

    /**
     * @return Collection<int, Organization>
     */
    public function handle(): Collection
    {
        return Organization::moderatedBy($this->requester)
            ->with('parent')
            ->orderBy(Organization::NODE_PATH)
            ->get();
    }
}
