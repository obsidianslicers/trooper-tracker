<?php

declare(strict_types=1);

namespace App\Messages\Organizations\PageData;

use App\Messages\Organizations\Resources\OrganizationRowResource;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Command Staff organization tree, in order, with what the actor may do on each row.
 *
 * @method static array call(Actor $actor)
 */
final class ListOrganizationsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    public function handle(): array
    {
        return [
            'organizations' => OrganizationRowResource::collection(
                Organization::withAllAssignments($this->actor)->get()
            ),
        ];
    }
}
