<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Organizations;

use App\Http\Requests\Admin\Organizations\CreateRequest;
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
    private Organization $parent_organization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateRequest();
        $this->parent_organization = Organization::factory()->create();
        $this->user = Trooper::factory()->asAdministrator()->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['parent' => $this->parent_organization]);
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

    public function test_validation_passes_with_valid_unique_name(): void
    {
        // Arrange
        $good_data = [
            'name' => 'New Child Organization',
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
        $existing_child = Organization::factory()->create([
            'parent_id' => $this->parent_organization->id,
            'name' => 'Existing Child Org',
        ]);

        $bad_data = [
            'name' => 'Existing Child Org',
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
            'name' => 'Child Organization',
        ]);

        // This should pass because it's under a different parent
        $good_data = [
            'name' => 'Child Organization',
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
            'name' => "501st Legion: Vader's Fist",
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
            'name' => '501st Legion',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_duplicate_name_case_sensitive(): void
    {
        // Arrange
        Organization::factory()->create([
            'parent_id' => $this->parent_organization->id,
            'name' => 'Test Organization',
        ]);

        $bad_data = [
            'name' => 'Test Organization',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }
}
