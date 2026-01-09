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
        $this->user = Trooper::factory()->create([
            'notification_frequency' => 'instant',
        ]);
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
            'notification_frequency' => 'daily',
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
            'notification_frequency' => 'daily',
        ];

        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_passes_with_valid_leaf_node_assignment(): void
    {
        // Arrange
        $leaf_node = Organization::factory()->unit()->create();
        $organization = $leaf_node->parent->parent;

        $good_data = [
            'email' => 'test@example.com',
            'notification_frequency' => 'daily',
            'organizations' => [
                $organization->id => [
                    'assignment' => $leaf_node->id,
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_assignment_is_not_leaf_node(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $bad_data = [
            'email' => 'test@example.com',
            'notification_frequency' => 'daily',
            'organizations' => [
                $organization->id => [
                    'assignment' => $region->id,
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization->id}.assignment"));
    }

    public function test_validation_fails_when_assignment_is_not_descendant_of_organization(): void
    {
        // Arrange
        $organization1 = Organization::factory()->create();
        $leaf_node1 = Organization::factory()->unit()->create(['parent_id' => $organization1->id]);

        $organization2 = Organization::factory()->create();

        $bad_data = [
            'email' => 'test@example.com',
            'notification_frequency' => 'daily',
            'organizations' => [
                $organization2->id => [
                    'assignment' => $leaf_node1->id,
                ],
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has("organizations.{$organization2->id}.assignment"));
    }

    public function test_validation_passes_when_assignment_is_null(): void
    {
        // Arrange
        $organization = Organization::factory()->create();

        $good_data = [
            'email' => 'test@example.com',
            'notification_frequency' => 'daily',
            'organizations' => [
                $organization->id => [
                    'assignment' => null,
                ],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
