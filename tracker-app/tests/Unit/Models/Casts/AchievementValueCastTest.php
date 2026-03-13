<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Enums\AchievementType;
use App\Models\Casts\AchievementValueCast;
use App\Models\TrooperAchievement;
use Tests\TestCase;

class AchievementValueCastTest extends TestCase
{
    public function test_number_type_is_cast_to_numeric_value(): void
    {
        $subject = new AchievementValueCast();

        $result = $subject->get(
            new TrooperAchievement(),
            TrooperAchievement::VALUE,
            '42',
            ['type' => AchievementType::TROOPER_SHIFTS->value]
        );

        $this->assertSame(42, $result);
    }

    public function test_bool_type_is_cast_to_boolean_value(): void
    {
        $subject = new AchievementValueCast();

        $result = $subject->get(
            new TrooperAchievement(),
            TrooperAchievement::VALUE,
            '1',
            ['type' => AchievementType::FIRST_TROOP->value]
        );

        $this->assertTrue($result);
    }

    public function test_set_converts_bool_type_to_string_flag(): void
    {
        $subject = new AchievementValueCast();

        $result = $subject->set(
            new TrooperAchievement(),
            TrooperAchievement::VALUE,
            true,
            ['type' => AchievementType::FIRST_TROOP->value]
        );

        $this->assertSame('1', $result);
    }
}