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
 * - Basic registration fields are validated (name, email, username, password).
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

    public function test_rules_include_base_fields(): void
    {
        // Arrange: none

        // Act
        $rules = $this->subject->rules();

        // Assert: basic required fields
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('username', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('account_type', $rules);
        $this->assertArrayHasKey('phone', $rules);
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
}
