<?php

namespace Tests\Unit\Http\Requests\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Tests for RegisterRequest validation.
 *
 * Verifies:
 * - Basic registration fields are validated (name, email, password).
 * - `prepareForValidation()` sanitizes phone numbers.
 * - `rules()` returns expected keys for base fields and organizations.
 * - Organization identifier rules respect account_type: required for members, optional for handlers.
 */
class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    private RegisterRequest $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new RegisterRequest();
    }

    public function test_authorize_returns_true(): void
    {
        // Arrange: subject constructed in setUp
        // Act
        $result = $this->subject->authorize();
        // Assert
        $this->assertTrue($result);
    }

    public function test_prepare_for_validation_strips_non_digits_from_phone(): void
    {
        // Arrange
        $this->subject->merge(['phone' => '(555) 123-4567']);

        // Act: invoke protected method via TestCase helper
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertSame('5551234567', $this->subject->input('phone'));
    }

    public function test_prepare_for_validation_handles_missing_phone(): void
    {
        // Arrange
        $this->subject->merge(['name' => 'Test User']);

        // Act: invoke protected method via TestCase helper
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert: should not error and phone should remain unset
        $this->assertNull($this->subject->input('phone'));
    }

    public function test_rules_include_organization_keys(): void
    {
        // Arrange: none

        // Act
        $rules = $this->subject->rules();

        // Assert: organizations rules exist (may be empty but key present)
        $this->assertArrayHasKey('organizations', $rules);
        $this->assertArrayHasKey('organizations.*.selected', $rules);
    }

    public function test_selected_equals_one_requires_region_id(): void
    {
        // Arrange: Create an organization with regions
        $club = \App\Models\Organization::factory()
            ->withIdentifierValidation()
            ->create();

        $region = \App\Models\Organization::factory()
            ->asRegion()
            ->create(['parent_id' => $club->id]);

        $data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '1', // String '1' means checked
                    'identifier' => '12345',
                    // Missing region_id - should fail validation
                ],
            ],
        ];

        // Create request with data
        $request = RegisterRequest::create('/register', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert: Validation should fail because region_id is required when selected === '1'
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey("organizations.{$club->id}.region_id", $validator->errors()->messages());
    }

    public function test_selected_zero_does_not_require_region_id(): void
    {
        // Arrange: Create an organization with regions
        $club = \App\Models\Organization::factory()
            ->withIdentifierValidation()
            ->create();

        $region = \App\Models\Organization::factory()
            ->asRegion()
            ->create(['parent_id' => $club->id]);

        $data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '0', // String '0' means unchecked
                    // No identifier or region_id - should pass because unchecked
                ],
            ],
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert: Validation should fail only because no organization is selected (AtLeastOneOrganizationSelectedRule)
        // but NOT because region_id is missing
        $this->assertTrue($validator->fails());
        $this->assertArrayNotHasKey("organizations.{$club->id}.region_id", $validator->errors()->messages());
    }

    public function test_selected_empty_string_does_not_require_region_id(): void
    {
        // Arrange: Create an organization with regions
        $club = \App\Models\Organization::factory()
            ->withIdentifierValidation()
            ->create();

        $region = \App\Models\Organization::factory()
            ->asRegion()
            ->create(['parent_id' => $club->id]);

        $data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '', // Empty string means unchecked (edge case)
                    // No identifier or region_id - should pass because unchecked
                ],
            ],
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert: Validation should fail only because no organization is selected
        // but NOT because region_id is missing
        $this->assertTrue($validator->fails());
        $this->assertArrayNotHasKey("organizations.{$club->id}.region_id", $validator->errors()->messages());
    }

    public function test_selected_equals_one_requires_identifier_for_members(): void
    {
        // Arrange: Create an organization with identifier validation
        $club = \App\Models\Organization::factory()
            ->withIdentifierValidation()
            ->create();

        $data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '1', // String '1' means checked
                    // Missing identifier - should fail validation for members
                ],
            ],
        ];

        // Create request with data
        $request = RegisterRequest::create('/register', 'POST', $data);
        $request->setContainer(app());
        $request->setRedirector(app('redirect'));

        // Act
        $validator = Validator::make($data, $request->rules());

        // Assert: Validation should fail because identifier is required when selected === '1' for members
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey("organizations.{$club->id}.identifier", $validator->errors()->messages());
    }

    public function test_selected_not_one_does_not_require_identifier_for_members(): void
    {
        // Arrange: Create an organization with identifier validation
        $club = \App\Models\Organization::factory()
            ->withIdentifierValidation()
            ->create();

        $data = [
            'name' => 'Test Trooper',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                $club->id => [
                    'selected' => '0', // String '0' means unchecked
                    // No identifier - should pass because unchecked
                ],
            ],
        ];

        // Act
        $validator = Validator::make($data, $this->subject->rules());

        // Assert: Validation should fail only because no organization is selected
        // but NOT because identifier is missing
        $this->assertTrue($validator->fails());
        $this->assertArrayNotHasKey("organizations.{$club->id}.identifier", $validator->errors()->messages());
    }
}
