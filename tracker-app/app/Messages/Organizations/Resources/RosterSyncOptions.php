<?php

declare(strict_types=1);

namespace App\Messages\Organizations\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The roster sync choices on the Command Staff organization update page: None, then each
 * synchronizer plugins offer, from a map of key => label.
 *
 * @property array<string, string> $resource
 */
class RosterSyncOptions extends JsonResource
{
    /**
     * @return array<int, array{value: string, label: string}>
     */
    public function toArray(Request $request): array
    {
        $options = [['value' => '', 'label' => __('admin.None')]];

        foreach ($this->resource as $value => $label)
        {
            $options[] = ['value' => (string) $value, 'label' => $label];
        }

        return $options;
    }
}
