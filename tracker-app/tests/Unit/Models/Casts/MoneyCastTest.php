<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Models\Casts\MoneyCast;
use App\Models\EventShift;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MoneyCastTest extends TestCase
{
    /**
     * @return array<string, array{mixed, float}>
     */
    public static function amounts(): array
    {
        return [
            'null' => [null, 0.0],
            'whole' => [500, 500.0],
            'decimal string from the database' => ['12.50', 12.5],
            'more than two places' => [12.345, 12.35],
        ];
    }

    #[DataProvider('amounts')]
    public function test_get_returns_dollars_rounded_to_cents(mixed $value, float $expected): void
    {
        $subject = new MoneyCast;

        $this->assertSame($expected, $subject->get(new EventShift, 'charity_direct_funds', $value, []));
    }

    #[DataProvider('amounts')]
    public function test_set_stores_dollars_rounded_to_cents(mixed $value, float $expected): void
    {
        $subject = new MoneyCast;

        $this->assertSame($expected, $subject->set(new EventShift, 'charity_direct_funds', $value, []));
    }
}
