<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\ModelChange;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TrooperChangeCollection extends ResourceCollection
{
    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (ModelChange $change): array => [
                'what' => $change->auditable_type,
                'label' => $change->auditable_label,
                'field' => $change->field_name,
                'old_value' => $change->old_value,
                'new_value' => $change->new_value,
                'when' => $change->created_at->diffForHumans(),
                'who' => $change->trooper->display_name ?? 'System',
            ])->values()->all();
    }
}
