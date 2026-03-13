<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\HasEnumHelpers;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HasEnumHelpersTest extends TestCase
{
    use DatabaseTransactions;

    public function test_to_array_sorts_by_case_name_and_formats_labels(): void
    {
        $result = array_map(static fn($label): string => (string) $label, HasEnumHelpersFixture::toArray());

        $this->assertSame([
            'alpha_value' => 'Alpha',
            'middle_value' => 'Middle',
            'zeta_value' => 'Zeta',
        ], $result);
    }

    public function test_to_validator_returns_sorted_comma_delimited_values(): void
    {
        $result = HasEnumHelpersFixture::toValidator();

        $this->assertSame('alpha_value,middle_value,zeta_value', $result);
    }
}

enum HasEnumHelpersFixture: string
{
    use HasEnumHelpers;

    case ZETA = 'zeta_value';
    case ALPHA = 'alpha_value';
    case MIDDLE = 'middle_value';
}
