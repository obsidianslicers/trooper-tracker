<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Models\Casts\LowerCast;
use App\Models\Trooper;
use Tests\TestCase;

class LowerCastTest extends TestCase
{
    public function test_set_lowercases_value(): void
    {
        $subject = new LowerCast();

        $result = $subject->set(new Trooper(), Trooper::EMAIL, 'TK-421@EXAMPLE.COM', []);

        $this->assertSame('tk-421@example.com', $result);
    }

    public function test_get_returns_raw_value(): void
    {
        $subject = new LowerCast();

        $result = $subject->get(new Trooper(), Trooper::EMAIL, 'tk-421@example.com', []);

        $this->assertSame('tk-421@example.com', $result);
    }
}