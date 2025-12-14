<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Http\Requests\Admin\Troopers\ProfileRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    private ProfileRequest $subject;
    private Trooper $admin;
    private Trooper $target_trooper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ProfileRequest();
        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->target_trooper = Trooper::factory()->create();

        $this->subject->setUserResolver(fn() => $this->admin);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['trooper' => $this->target_trooper]);
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

    public function test_authorize_returns_true_for_moderator_of_same_organization(): void
    {
        // Arrange
        $unit = Organization::factory()->unit()->create();
        $region = $unit->parent;
        $organization = $region->parent;

        $moderator = Trooper::factory()->asModerator()
            ->withAssignment($region, moderator: true)
            ->create();

        TrooperAssignment::factory()->create([
            'trooper_id' => $this->target_trooper->id,
            'organization_id' => $unit->id,
            'is_member' => true
        ]);



        $this->subject->setUserResolver(fn() => $moderator);

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

    public function test_authorize_throws_exception_when_trooper_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Trooper not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $good_data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'membership_status' => MembershipStatus::ACTIVE->value,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_minimal_data(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
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
            'email' => 'test@example.com',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_missing_email(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_invalid_email_format(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => 'not-an-email',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => str_repeat('a', 257),
            'email' => 'test@example.com',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_email_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => str_repeat('a', 250) . '@test.com',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_phone_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => str_repeat('1', 17),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('phone'));
    }

    public function test_validation_passes_with_null_phone(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => null,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_null_membership_status(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'membership_status' => null,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_membership_status(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'membership_status' => 'invalid-status',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('membership_status'));
    }

    public function test_validation_passes_with_all_membership_statuses(): void
    {
        // Arrange
        $statuses = [
            MembershipStatus::PENDING->value,
            MembershipStatus::ACTIVE->value,
            MembershipStatus::RETIRED->value,
        ];

        foreach ($statuses as $status)
        {
            $good_data = [
                'name' => 'Test User',
                'email' => 'test@example.com',
                'membership_status' => $status,
            ];

            // Act
            $this->subject->merge($good_data);
            $validator = Validator::make($good_data, $this->subject->rules());

            // Assert
            $this->assertTrue($validator->passes(), "Failed for status: {$status}");
        }
    }

    public function test_prepare_for_validation_sanitizes_phone_number(): void
    {
        // Arrange
        $input_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '(555) 123-4567',
        ];

        // Act
        $this->subject->merge($input_data);
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertEquals('5551234567', $this->subject->input('phone'));
    }

    public function test_prepare_for_validation_handles_missing_phone(): void
    {
        // Arrange
        $input_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        // Act
        $this->subject->merge($input_data);
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertNull($this->subject->input('phone'));
    }

    public function test_prepare_for_validation_handles_empty_phone(): void
    {
        // Arrange
        $input_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '',
        ];

        // Act
        $this->subject->merge($input_data);
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertEquals('', $this->subject->input('phone'));
    }

    public function test_validation_passes_with_name_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'name' => str_repeat('a', 256),
            'email' => 'test@example.com',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_email_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test User',
            'email' => str_repeat('a', 246) . '@test.com', // 256 total
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_phone_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => str_repeat('1', 16),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
