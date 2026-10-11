<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries\Membership;

use App\Models\Trooper;
use App\Models\TrooperRequest;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Returns the trooper requests for the given trooper
 *
 * @method static Collection call(Trooper $trooper)
 */
final class GetDeniedTrooperRequests extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {}

    public function handle(): Collection
    {
        $requests = TrooperRequest::query()
            ->denied()
            ->where(TrooperRequest::TROOPER_ID, $this->trooper->id)
            ->orderByDesc(TrooperRequest::UPDATED_AT)
            ->get();

        return $requests;
    }
}
