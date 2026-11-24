<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources\Record;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Who the service record is for (service-records/Trooper): the header, and what the viewer may
 * do from it.
 *
 * @property Trooper $resource
 */
class ServiceRecordTrooperResource extends JsonResource
{
    /**
     * @param  array{rank_title: string, rank_theme: string}  $summary
     */
    public function __construct(
        Trooper $trooper,
        private readonly array $summary,
        private readonly bool $can_update,
        private readonly bool $has_missing_credit,
        private readonly bool $is_self,
    ) {
        parent::__construct($trooper);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trooper = $this->resource;

        return [
            Trooper::ID => $trooper->id,
            Trooper::DISPLAY_NAME => $trooper->display_name,
            'rank_title' => $this->summary['rank_title'],
            'rank_theme' => $this->summary['rank_theme'],
            'is_departed' => $trooper->membership_status === MembershipStatus::DEPARTED,
            'is_handler' => (bool) $trooper->is_handler,
            'is_self' => $this->is_self,
            'edit_url' => $this->can_update ? route('admin.troopers.show', ['trooper' => $trooper]) : null,
            'missing_credit_url' => $this->can_update && $this->has_missing_credit
                ? route('admin.service-records.missing-credits.index', ['trooper_id' => $trooper->id])
                : null,
        ];
    }
}
