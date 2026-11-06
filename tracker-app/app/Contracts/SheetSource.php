<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Exceptions\SheetUnavailableException;
use App\Models\Organization;

/**
 * Where club synchronizers read their roster spreadsheets from, such as Google Sheets.
 */
interface SheetSource
{
    /**
     * Every row of one sheet in the organization's spreadsheet, header row
     * included.
     *
     * @return array<int, array<int, string>>|null Null when the organization has
     *                                             no spreadsheet set.
     *
     * @throws SheetUnavailableException when the spreadsheet cannot be read
     */
    public function rows(Organization $organization, string $sheet_name): ?array;
}
