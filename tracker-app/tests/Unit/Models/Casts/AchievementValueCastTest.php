<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Enums\AchievementType;
use App\Models\Casts\AchievementValueCast;
use Illuminate\Database\Eloquent\Model;
use Mockery;
use Tests\TestCase;

/**
 * @see \App\Models\Casts\AchievementValueCast
 */
class AchievementValueCastTest extends TestCase
{
    private AchievementValueCast $subject;
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new AchievementValueCast();
        $this->model = Mockery::mock(Model::class);
    }

    public function test_get_casts_number_type_to_integer(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::TROOPER_RANK->value];

        // Act
        $result = $this->subject->get($this->model, 'value', '42', $attributes);

        // Assert
        $this->assertSame(42, $result);
    }

    public function test_get_casts_number_type_to_float(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::VOLUNTEER_HOURS->value];

        // Act
        $result = $this->subject->get($this->model, 'value', '123.45', $attributes);

        // Assert
        $this->assertSame(123.45, $result);
    }

    public function test_get_returns_null_for_non_numeric_number_type(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::TROOPER_SHIFTS->value];

        // Act
        $result = $this->subject->get($this->model, 'value', 'invalid', $attributes);

        // Assert
        $this->assertNull($result);
    }

    public function test_get_casts_bool_type_to_true(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::FIRST_TROOP->value];

        // Act
        $result = $this->subject->get($this->model, 'value', '1', $attributes);

        // Assert
        $this->assertTrue($result);
    }

    public function test_get_casts_bool_type_to_false(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::TROOPED_10->value];

        // Act
        $result = $this->subject->get($this->model, 'value', '0', $attributes);

        // Assert
        $this->assertFalse($result);
    }

    public function test_set_converts_number_to_string(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::DIRECT_FUNDS->value];

        // Act
        $result = $this->subject->set($this->model, 'value', 1000, $attributes);

        // Assert
        $this->assertSame('1000', $result);
    }

    public function test_set_converts_float_to_string(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::INDIRECT_FUNDS->value];

        // Act
        $result = $this->subject->set($this->model, 'value', 250.75, $attributes);

        // Assert
        $this->assertSame('250.75', $result);
    }

    public function test_set_converts_true_to_one(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::TROOPED_ALL_SQUADS->value];

        // Act
        $result = $this->subject->set($this->model, 'value', true, $attributes);

        // Assert
        $this->assertSame('1', $result);
    }

    public function test_set_converts_false_to_zero(): void
    {
        // Arrange
        $attributes = ['type' => AchievementType::TROOPED_25->value];

        // Act
        $result = $this->subject->set($this->model, 'value', false, $attributes);

        // Assert
        $this->assertSame('0', $result);
    }

    public function test_handles_all_number_achievement_types(): void
    {
        // Arrange
        $number_types = [
            AchievementType::TROOPER_RANK,
            AchievementType::TROOPER_SHIFTS,
            AchievementType::VOLUNTEER_HOURS,
            AchievementType::DIRECT_FUNDS,
            AchievementType::INDIRECT_FUNDS,
        ];

        foreach ($number_types as $type)
        {
            // Act
            $attributes = ['type' => $type->value];
            $get_result = $this->subject->get($this->model, 'value', '100', $attributes);
            $set_result = $this->subject->set($this->model, 'value', 100, $attributes);

            // Assert
            $this->assertSame(100, $get_result, "Failed for type: {$type->value}");
            $this->assertSame('100', $set_result, "Failed for type: {$type->value}");
        }
    }

    public function test_handles_all_boolean_achievement_types(): void
    {
        // Arrange
        $boolean_types = [
            AchievementType::TROOPED_ALL_SQUADS,
            AchievementType::FIRST_TROOP,
            AchievementType::TROOPED_10,
            AchievementType::TROOPED_25,
            AchievementType::TROOPED_50,
            AchievementType::TROOPED_75,
            AchievementType::TROOPED_100,
            AchievementType::TROOPED_150,
            AchievementType::TROOPED_200,
            AchievementType::TROOPED_250,
            AchievementType::TROOPED_300,
            AchievementType::TROOPED_400,
            AchievementType::TROOPED_500,
            AchievementType::TROOPED_501,
        ];

        foreach ($boolean_types as $type)
        {
            // Act
            $attributes = ['type' => $type->value];
            $get_result = $this->subject->get($this->model, 'value', '1', $attributes);
            $set_result = $this->subject->set($this->model, 'value', true, $attributes);

            // Assert
            $this->assertTrue($get_result, "Failed for type: {$type->value}");
            $this->assertSame('1', $set_result, "Failed for type: {$type->value}");
        }
    }
}
