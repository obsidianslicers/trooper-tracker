<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Models\TrooperDonation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A donation on the service record's Support tab.
 *
 * @property TrooperDonation $resource
 */
class DonationRowResource extends JsonResource
{
    /**
     * @return array{id: int, date: string|null, amount: string}
     */
    public function toArray(Request $request): array
    {
        return [
            TrooperDonation::ID => $this->resource->id,
            'date' => $this->resource->created_at?->localFormat('M j, Y'),
            'amount' => format_number($this->resource->amount, 2),
        ];
    }
}
