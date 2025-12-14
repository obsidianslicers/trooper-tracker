<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Awards;

use App\Enums\AwardFrequency;
use App\Http\Requests\Admin\Awards\CreateRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private CreateRequest $subject;
    private Trooper $user;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->asModerator()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->subject->setUserResolver(fn() => $this->user);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test Award',
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_name(): void
    {
        // Arrange
        $bad_data = [
            'name' => '',
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => str_repeat('a', 129),
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_missing_frequency(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Award',
            'frequency' => '',
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('frequency'));
    }

    public function test_validation_fails_with_invalid_frequency(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Award',
            'frequency' => 'invalid-frequency',
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('frequency'));
    }

    public function test_validation_fails_with_missing_organization_id(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Award',
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_fails_with_nonexistent_organization_id(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test Award',
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => 999999,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_fails_with_organization_not_moderated_by_user(): void
    {
        // Arrange
        $unmoderated_org = Organization::factory()->create();

        $bad_data = [
            'name' => 'Test Award',
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => $unmoderated_org->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_passes_with_child_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        $this->subject->setUserResolver(fn() => $moderator);

        $good_data = [
            'name' => 'Test Award',
            'frequency' => AwardFrequency::MONTHLY->value,
            'organization_id' => $unit->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_all_frequency_types(): void
    {
        // Arrange
        $frequencies = [
            AwardFrequency::MONTHLY->value,
            AwardFrequency::ANNUALLY->value,
            AwardFrequency::ONCE->value,
        ];

        foreach ($frequencies as $frequency)
        {
            $good_data = [
                'name' => 'Test Award - ' . $frequency,
                'frequency' => $frequency,
                'organization_id' => $this->organization->id,
            ];

            // Act
            $this->subject->merge($good_data);
            $validator = Validator::make($good_data, $this->subject->rules());

            // Assert
            $this->assertTrue($validator->passes(), "Failed for frequency: {$frequency}");
        }
    }
}
