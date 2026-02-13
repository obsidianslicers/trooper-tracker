<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Http\Requests\Admin\Awards\AssignTrooperRequest;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AssignTrooperRequestTest extends TestCase
{
    use RefreshDatabase;

    private AssignTrooperRequest $subject;
    private Award $award;
    private Trooper $admin;
    private Trooper $trooper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->award = Award::factory()->create(['frequency' => AwardFrequency::ONCE->value]);
        $this->trooper = Trooper::factory()->asActive()->create();
        $this->admin = Trooper::factory()->asAdministrator()->create();

        $this->subject = new AssignTrooperRequest();
        $this->subject->setUserResolver(fn() => $this->admin);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['award' => $this->award]);
        });
    }

    private function getMockRoute(array $parameters = []): object
    {
        return new class ($parameters)
        {
            public function __construct(private array $parameters)
            {
            }

            public function parameter(string $key, $default = null)
            {
                return $this->parameters[$key] ?? $default;
            }
        };
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $award = Award::factory()->for($organization)->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $moderator);
        $this->subject->setRouteResolver(function () use ($award)
        {
            return $this->getMockRoute(['award' => $award]);
        });

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_trooper(): void
    {
        // Arrange
        $regular_trooper = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $regular_trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_validation_passes_with_valid_trooper_and_date(): void
    {
        // Arrange
        $good_data = [
            AwardTrooper::TROOPER_ID => $this->trooper->id,
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_trooper_id(): void
    {
        // Arrange
        $bad_data = [
            AwardTrooper::TROOPER_ID => '',
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(AwardTrooper::TROOPER_ID));
    }

    public function test_validation_fails_with_missing_award_date(): void
    {
        // Arrange
        $bad_data = [
            AwardTrooper::TROOPER_ID => $this->trooper->id,
            AwardTrooper::AWARD_DATE => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(AwardTrooper::AWARD_DATE));
    }

    public function test_validation_fails_with_non_existent_trooper(): void
    {
        // Arrange
        $bad_data = [
            AwardTrooper::TROOPER_ID => 9999,
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(AwardTrooper::TROOPER_ID));
    }

    public function test_validation_fails_with_invalid_date_format(): void
    {
        // Arrange
        $bad_data = [
            AwardTrooper::TROOPER_ID => $this->trooper->id,
            AwardTrooper::AWARD_DATE => 'not-a-date',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(AwardTrooper::AWARD_DATE));
    }

    public function test_validation_fails_with_duplicate_award(): void
    {
        // Arrange
        $award_date = now()->toDateString();
        \DB::table('tt_award_troopers')->insert([
            'award_id' => $this->award->id,
            'trooper_id' => $this->trooper->id,
            'award_date' => $award_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $bad_data = [
            AwardTrooper::TROOPER_ID => $this->trooper->id,
            AwardTrooper::AWARD_DATE => $award_date,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(AwardTrooper::TROOPER_ID));
    }

    public function test_validation_passes_for_same_trooper_different_date(): void
    {
        // Arrange
        $first_date = now()->subMonth()->toDateString();
        \DB::table('tt_award_troopers')->insert([
            'award_id' => $this->award->id,
            'trooper_id' => $this->trooper->id,
            'award_date' => $first_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $good_data = [
            AwardTrooper::TROOPER_ID => $this->trooper->id,
            AwardTrooper::AWARD_DATE => now()->toDateString(),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_enforces_frequency_constraints(): void
    {
        // Arrange
        $monthly_award = Award::factory()->create(['frequency' => AwardFrequency::MONTHLY->value]);
        $this->subject->setRouteResolver(function () use ($monthly_award)
        {
            return $this->getMockRoute(['award' => $monthly_award]);
        });

        $bad_data = [
            AwardTrooper::TROOPER_ID => $this->trooper->id,
            AwardTrooper::AWARD_DATE => now()->setDay(15)->toDateString(),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has(AwardTrooper::AWARD_DATE));
    }
}
