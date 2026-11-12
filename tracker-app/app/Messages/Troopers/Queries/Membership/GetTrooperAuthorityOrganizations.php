<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Organizations of every type with the trooper's assignment (moderator, notify, member) loaded.
 *
 * @method static Collection<int, Organization> call(Trooper $trooper)
 */
final class GetTrooperAuthorityOrganizations extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    public function handle(): Collection
    {
        return Organization::withAllAssignments($this->trooper)->get();
    }
}
