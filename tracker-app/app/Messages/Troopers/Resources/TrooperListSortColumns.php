<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * The columns the Command Staff troopers list (admin/troopers/List) can be sorted by, in header
 * order.
 */
class TrooperListSortColumns extends ResourceCollection
{
    /**
     * @param  array<string, string>  $columns  column => header label
     */
    public function __construct(array $columns)
    {
        parent::__construct(collect($columns));
    }

    /**
     * @return array<int, array{key: string, label: string}>
     */
    public function toArray(Request $request): array
    {
        return $this->collection
            ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
            ->values()
            ->all();
    }
}
