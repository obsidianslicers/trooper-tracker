<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Troopers;

use App\Enums\MembershipRole;
use App\Http\Requests\Admin\Troopers\AuthorityRequest;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AuthorityRequestTest extends TestCase
{
    use RefreshDatabase;

    private AuthorityRequest $subject;
    private Trooper $admin;
    private Trooper $target_trooper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new AuthorityRequest();
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

    public function test_authorize_returns_false_for_moderator(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $this->subject->setUserResolver(fn() => $moderator);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
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

    public function test_validation_passes_with_valid_membership_role_member(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MEMBER->value,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_valid_membership_role_moderator(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MODERATOR->value,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_valid_membership_role_administrator(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::ADMINISTRATOR->value,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_null_membership_role(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => null,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_membership_role(): void
    {
        // Arrange
        $bad_data = [
            'membership_role' => 'invalid-role',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('membership_role'));
    }

    public function test_validation_fails_with_membership_role_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'membership_role' => str_repeat('a', 17),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('membership_role'));
    }

    public function test_validation_passes_with_moderator_selections(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MODERATOR->value,
            'moderators' => [
                1 => ['selected' => true],
                2 => ['selected' => false],
                3 => ['selected' => true],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_non_boolean_moderator_selection(): void
    {
        // Arrange
        $bad_data = [
            'membership_role' => MembershipRole::MODERATOR->value,
            'moderators' => [
                1 => ['selected' => 'yes'], // Should be boolean
            ],
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('moderators.1.selected'));
    }

    public function test_validation_passes_with_empty_moderators_array(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MEMBER->value,
            'moderators' => [],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_without_moderators_field(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MEMBER->value,
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_all_membership_roles(): void
    {
        // Arrange
        $roles = [
            MembershipRole::MEMBER->value,
            MembershipRole::MODERATOR->value,
            MembershipRole::ADMINISTRATOR->value,
        ];

        foreach ($roles as $role)
        {
            $good_data = [
                'membership_role' => $role,
            ];

            // Act
            $this->subject->merge($good_data);
            $validator = Validator::make($good_data, $this->subject->rules());

            // Assert
            $this->assertTrue($validator->passes(), "Failed for role: {$role}");
        }
    }

    public function test_validation_passes_with_multiple_moderator_selections(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MODERATOR->value,
            'moderators' => [
                10 => ['selected' => true],
                20 => ['selected' => false],
                30 => ['selected' => true],
                40 => ['selected' => false],
                50 => ['selected' => true],
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_numeric_boolean_values(): void
    {
        // Arrange
        $good_data = [
            'membership_role' => MembershipRole::MODERATOR->value,
            'moderators' => [
                1 => ['selected' => 1], // Numeric 1 is considered boolean true
                2 => ['selected' => 0], // Numeric 0 is considered boolean false
            ],
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
