<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Sends rows a message built as a CSV download.
 */
trait StreamsCsvDownloads
{
    /**
     * @param  iterable<int, array<int, mixed>>  $rows
     */
    private function streamCsv(string $filename, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows)
        {
            $handle = fopen('php://output', 'w');

            foreach ($rows as $row)
            {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
