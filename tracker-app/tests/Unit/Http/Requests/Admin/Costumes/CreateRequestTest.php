<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Admin\Costumes;

use App\Http\Requests\Admin\Costumes\CreateRequest;
use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Unit tests for CreateRequest.
 *
 * Verifies:
 * - Authorization logic (only administrators can create costumes)
 * - Validation rules for costume name field
 * - Unique constraint on costume names
 */
class CreateRequestTest extends TestCase
{
    use RefreshDatabase;

    private CreateRequest $subject;
    private Trooper $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new CreateRequest();
        $this->user = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $this->user);
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

    public function test_validation_passes_with_valid_name(): void
    {
        // Arrange
        $data = [
            'name' => 'Stormtrooper',
        ];

        // Act
        $this->subject->merge($data);
        $validator = Validator::make($data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_passes_with_unique_name(): void
    {
        // Arrange
        Costume::factory()->create(['name' => 'First Costume']);

        $data = [
            'name' => 'Second Costume',
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

    public function test_validation_fails_with_duplicate_name(): void
    {
        // Arrange
        Costume::factory()->create(['name' => 'Existing Costume']);

        $data = [
            'name' => 'Existing Costume',
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
            'Stormtrooper',
            'Clone Trooper',
            'Royal Guard',
            'First Order Stormtrooper',
            'TK-421',
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

    public function test_name_rules_includes_unique_constraint(): void
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
}
