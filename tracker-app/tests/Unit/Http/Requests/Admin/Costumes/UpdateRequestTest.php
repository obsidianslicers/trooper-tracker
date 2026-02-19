<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Costumes;

use App\Http\Requests\Admin\Costumes\UpdateRequest;
use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for UpdateRequest.
 *
 * Verifies:
 * - Authorization logic (only administrators can update costumes)
 * - Costume must exist in the route, otherwise throws AuthorizationException
 * - Validation rules for costume name field
 * - Unique constraint on costume names, excluding the costume being updated
 */
class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private UpdateRequest $subject;
    private Trooper $user;
    private Costume $costume;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new UpdateRequest();
        $this->user = Trooper::factory()->asAdministrator()->create();
        $this->costume = Costume::factory()->create(['name' => 'Original Costume']);

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['costume' => $this->costume]);
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
        // Act & Assert
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
        $trooper = Trooper::factory()->asActive()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_pending_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asPending()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_retired_trooper(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asRetired()->create();
        $this->subject->setUserResolver(fn() => $trooper);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_throws_exception_when_costume_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Costume not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_authorize_throws_exception_when_costume_is_null(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['costume' => null]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Costume not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_updated_name(): void
    {
        // Arrange
        $data = [
            'name' => 'Updated Costume Name',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_when_keeping_same_name(): void
    {
        // Arrange
        $data = [
            'name' => 'Original Costume',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_missing_name(): void
    {
        // Arrange
        $data = [
            'name' => '',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_if_name_is_not_provided(): void
    {
        // Arrange
        $data = [];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $data = [
            'name' => str_repeat('a', 129),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_passes_with_name_at_max_length(): void
    {
        // Arrange
        $data = [
            'name' => str_repeat('a', 128),
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_non_string_name(): void
    {
        // Arrange
        $data = [
            'name' => 12345,
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_fails_with_duplicate_name_from_other_costume(): void
    {
        // Arrange
        Costume::factory()->create(['name' => 'Another Costume']);

        $data = [
            'name' => 'Another Costume',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('name'));
    }

    public function test_validation_passes_with_various_valid_names(): void
    {
        // Arrange
        $valid_names = [
            'Updated Stormtrooper',
            'Clone Trooper Variant',
            'Royal Guard Elite',
            'First Order Stormtrooper Updated',
            'TK-421-Updated',
        ];

        foreach ($valid_names as $name)
        {
            $data = ['name' => $name];

            // Act
            $this->subject->merge($data);
            $validator = Validator::make($data, $this->subject->rules());

            // Assert
            $this->assertTrue($validator->passes(), "Failed for name: {$name}");
        }
    }

    public function test_rules_returns_array(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
    }

    public function test_name_rules_includes_required(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertIsArray($rules['name']);
        $this->assertContains('required', $rules['name']);
    }

    public function test_name_rules_includes_string(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertIsArray($rules['name']);
        $this->assertContains('string', $rules['name']);
    }

    public function test_name_rules_includes_max_constraint(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertIsArray($rules['name']);
        $this->assertTrue(
            collect($rules['name'])->some(fn($rule) => is_string($rule) && str_contains($rule, 'max:128')),
            'max:128 rule not found'
        );
    }

    public function test_name_rules_includes_unique_constraint_with_ignore(): void
    {
        // Act
        $rules = $this->subject->rules();

        // Assert
        $this->assertIsArray($rules['name']);
        $this->assertTrue(
            collect($rules['name'])->some(fn($rule) => is_object($rule) || (is_string($rule) && str_contains($rule, 'unique'))),
            'unique rule not found'
        );
    }

    public function test_validation_ignores_current_costume_in_unique_check(): void
    {
        // Arrange
        // Create another costume with a different name
        $other_costume = Costume::factory()->create(['name' => 'Other Costume']);

        // Try to update current costume back to its original name (should pass)
        $data = [
            'name' => $this->costume->name,
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes(), 'Should allow keeping the same name');
    }
}
