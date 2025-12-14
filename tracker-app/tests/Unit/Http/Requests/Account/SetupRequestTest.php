<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Account;

use App\Http\Requests\Account\SetupRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SetupRequestTest extends TestCase
{
    use RefreshDatabase;

    private SetupRequest $subject;
    private Trooper $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new SetupRequest();
        $this->user = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $this->user);
    }

    public function test_authorize_returns_true(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_validation_fails_with_missing_email(): void
    {
        $bad_data = [
            'email' => '',
        ];

        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_invalid_email_format(): void
    {
        $bad_data = [
            'email' => 'invalid-email',
        ];

        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_no_organizations_selected(): void
    {
        $bad_data = [
            'email' => 'test@example.com',
            'organizations' => [],
        ];

        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('organizations'));
    }

    public function test_validation_fails_when_organization_selected_without_region(): void
    {
        // Arrange
        $region = Organization::factory()->region()->create();
        $organization = $region->parent;

        $bad_data = [
            'email' => 'test@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'region_id' => null,
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.region_id"));
    }

    public function test_validation_fails_when_region_selected_without_unit(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $bad_data = [
            'email' => 'test@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'region_id' => $region->id,
                    'unit_id' => null,
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.unit_id"));
    }

    public function test_validation_passes_with_valid_organization_selection(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $good_data = [
            'email' => 'test@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'region_id' => $region->id,
                    'unit_id' => $unit->id,
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_organization_not_selected(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        $good_data = [
            'email' => 'test@example.com',
            'organizations' => [
                $organization->id => [
                    'selected' => '0',
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertfalse($validator->passes());
    }
}
