<?php

declare(strict_types=1);

namespace App\Messages\Reports\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A link per report column that sorts by it, flipping the direction when it is already the sort
 * (admin/reports summaries).
 *
 * @property array<int, string> $resource the columns
 */
class ReportSortLinks extends JsonResource
{
    /**
     * @param  array<int, string>  $columns
     */
    public function __construct(array $columns, private readonly string $sort, private readonly string $dir)
    {
        parent::__construct($columns);
    }

    /**
     * @return array<string, array{url: string, arrow: string}>
     */
    public function toArray(Request $request): array
    {
        $links = [];

        foreach ($this->resource as $column)
        {
            $active = $this->sort === $column;

            $links[$column] = [
                'url' => $request->fullUrlWithQuery([
                    'sort' => $column,
                    'dir' => ($active && $this->dir === 'asc') ? 'desc' : 'asc',
                    'page' => 1,
                ]),
                'arrow' => $active ? ($this->dir === 'asc' ? '↑' : '↓') : '',
            ];
        }

        return $links;
    }
}
