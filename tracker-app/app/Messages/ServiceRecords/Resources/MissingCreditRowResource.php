<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One attended shift with no club credited, on the missing-credit tool
 * (admin/service-records/MissingCredits).
 *
 * @property array<string, mixed> $resource a row from GetEventTroopersMissingCredit
 */
class MissingCreditRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'event_trooper_id' => $this->resource['event_trooper_id'],
            'trooper_id' => $this->resource['trooper_id'],
            'trooper_name' => $this->resource['trooper_name'],
            'event_name' => $this->resource['event_name'],
            'shift_label' => $this->resource['shift_label'],
            'costume_name' => $this->resource['costume_name'],
            'org_options' => $this->resource['org_options'],
            'has_orphaned_db_value' => $this->resource['has_orphaned_db_value'],
            'fallback_org_options' => $this->resource['fallback_org_options'],
            'trooper_has_no_club_membership' => $this->resource['trooper_has_no_club_membership'],
        ];
    }
}
