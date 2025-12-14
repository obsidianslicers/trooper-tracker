<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Organizations;

use App\Http\Requests\Admin\Organizations\UpdateRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateRequest $subject;
    private Trooper $user;
    private Organization $parent_organization;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateRequest();
        $this->parent_organization = Organization::factory()->create();
        $this->organization = Organization::factory()->create([
            'parent_id' => $this->parent_organization->id,
            'name' => 'Original Organization Name',
        ]);
        $this->user = Trooper::factory()->asAdministrator()->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['organization' => $this->organization]);
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

    public function test_authorize_throws_exception_when_organization_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Organization not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_new_unique_name(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Updated Organization Name',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_same_name_no_change(): void
    {
        // Arrange
        $good_data = [
            'name' => 'Original Organization Name',
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
            'name' => str_repeat('a', 65),
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_passes_with_name_at_max_length(): void
    {
        // Arrange
        $good_data = [
            'name' => str_repeat('a', 64),
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_duplicate_sibling_name(): void
    {
        // Arrange
        $sibling = Organization::factory()->create([
            'parent_id' => $this->parent_organization->id,
            'name' => 'Sibling Organization',
        ]);

        $bad_data = [
            'name' => 'Sibling Organization',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_passes_with_same_name_under_different_parent(): void
    {
        // Arrange
        $other_parent = Organization::factory()->create();
        Organization::factory()->create([
            'parent_id' => $other_parent->id,
            'name' => 'Organization Name',
        ]);

        // This should pass because it's under a different parent
        $good_data = [
            'name' => 'Organization Name',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_name_containing_special_characters(): void
    {
        // Arrange
        $good_data = [
            'name' => "501st Legion: Vader's Fist - Updated",
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_name_containing_numbers(): void
    {
        // Arrange
        $good_data = [
            'name' => '501st Legion - Updated',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_excludes_current_organization_from_uniqueness_check(): void
    {
        // Arrange
        // Create another organization with a different name
        Organization::factory()->create([
            'parent_id' => $this->parent_organization->id,
            'name' => 'Another Organization',
        ]);

        // Try to update to the current organization's own name (should pass)
        $good_data = [
            'name' => 'Original Organization Name',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }
}
