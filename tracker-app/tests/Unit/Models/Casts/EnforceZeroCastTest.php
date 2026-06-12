<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Models\Casts\EnforceZeroCast;
use App\Models\EventShift;
use Tests\TestCase;

class EnforceZeroCastTest extends TestCase
{
    public function test_get_returns_raw_integer_value(): void
    {
        $subject = new EnforceZeroCast();

        $result = $subject->get(new EventShift(), EventShift::CHARITY_DIRECT_FUNDS, 25, []);

        $this->assertSame(25, $result);
    }

    public function test_set_converts_null_to_zero(): void
    {
        $subject = new EnforceZeroCast();

        $result = $subject->set(new EventShift(), EventShift::CHARITY_DIRECT_FUNDS, null, []);

        $this->assertSame(0, $result);
    }

    public function test_set_preserves_non_null_integer_value(): void
    {
        $subject = new EnforceZeroCast();

        $result = $subject->set(new EventShift(), EventShift::CHARITY_INDIRECT_FUNDS, 10, []);

        $this->assertSame(10, $result);
    }
}
