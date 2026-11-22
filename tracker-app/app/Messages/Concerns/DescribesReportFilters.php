<?php

declare(strict_types=1);

namespace App\Messages\Concerns;

use Carbon\Carbon;

/**
 * Captions and sort links for the reports that filter by date and sort by column.
 */
trait DescribesReportFilters
{
    protected function dateRange(?Carbon $date_start, ?Carbon $date_end): string
    {
        if (!$date_start && !$date_end)
        {
            return __('admin.all time');
        }

        $parts = [];

        if ($date_start)
        {
            $parts[] = __('admin.from :date', ['date' => $date_start->localFormat('M d, Y')]);
        }

        if ($date_end)
        {
            $parts[] = __('admin.to :date', ['date' => $date_end->localFormat('M d, Y')]);
        }

        return implode(' ', $parts);
    }

    /**
     * A link per column that sorts by it, flipping the direction when it is already the sort.
     *
     * @param  array<int, string>  $columns
     * @return array<string, array{url: string, arrow: string}>
     */
    protected function sortLinks(array $columns, string $sort, string $dir): array
    {
        $links = [];

        foreach ($columns as $column)
        {
            $active = $sort === $column;

            $links[$column] = [
                'url' => request()->fullUrlWithQuery([
                    'sort' => $column,
                    'dir' => ($active && $dir === 'asc') ? 'desc' : 'asc',
                    'page' => 1,
                ]),
                'arrow' => $active ? ($dir === 'asc' ? '↑' : '↓') : '',
            ];
        }

        return $links;
    }

    protected function csvUrl(): string
    {
        return request()->fullUrlWithQuery(['format' => 'csv']);
    }
}
