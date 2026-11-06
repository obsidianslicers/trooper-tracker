<?php

declare(strict_types=1);

namespace Tests\Fixtures\Sheets;

use App\Contracts\SheetSource;
use App\Models\Organization;
use LogicException;

/**
 * A sheet source for core tests to mock, so they don't need a real plugin.
 */
class FakeSheetSource implements SheetSource
{
    public function rows(Organization $organization, string $sheet_name): ?array
    {
        throw new LogicException('Mock FakeSheetSource in the test.');
    }
}
