<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Events;

use App\Http\Requests\Admin\Events\CreateRequest;
use App\Models\Event;
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
            'source' => 'Test event source email content',
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_source(): void
    {
        // Arrange
        $bad_data = [
            'source' => '',
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('source'));
    }

    public function test_validation_fails_with_missing_organization_id(): void
    {
        // Arrange
        $bad_data = [
            'source' => 'Test event source',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_fails_with_invalid_organization_id(): void
    {
        // Arrange
        $bad_data = [
            'source' => 'Test event source',
            'organization_id' => 99999,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_fails_with_non_moderated_organization(): void
    {
        // Arrange
        $different_org = Organization::factory()->create();
        $bad_data = [
            'source' => 'Test event source',
            'organization_id' => $different_org->id,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organization_id'));
    }

    public function test_validation_passes_with_organization_moderated_by_user(): void
    {
        // Arrange
        $good_data = [
            'source' => 'Test event source',
            'organization_id' => $this->organization->id,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
