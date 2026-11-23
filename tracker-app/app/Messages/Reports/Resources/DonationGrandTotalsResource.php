<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use App\Messages\Concerns\SumsDonationAmounts;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The totals across every event on one page of the Donation Event Summary
 * (admin/reports/DonationEventSummary).
 *
 * @property Collection<int, Event> $resource
 */
class DonationGrandTotalsResource extends JsonResource
{
    use SumsDonationAmounts;

    /**
     * @return array<string, string>
     */
    public function toArray(Request $request): array
    {
        return $this->amounts($this->resource->flatMap(fn (Event $event) => $event->event_shifts), true);
    }
}
