<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Account;

use App\Enums\TrooperTheme;
use App\Http\Requests\Account\ProfileRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    private ProfileRequest $subject;
    private Trooper $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ProfileRequest();
        $this->user = Trooper::factory()->create();
        $this->subject->setUserResolver(fn() => $this->user);
    }

    public function test_authorize_returns_true(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_validation_passes_with_valid_data(): void
    {
        // Arrange
        $good_data = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '555-1234',
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::SITH->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
    }

    public function test_validation_fails_with_missing_theme(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'theme' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('theme'));
    }

    public function test_validation_fails_with_invalid_theme(): void
    {
        // Arrange
        $bad_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'theme' => 'invalid-theme',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('theme'));
    }

    public function test_validation_fails_with_name_exceeding_max_length(): void
    {
        // Arrange
        $bad_data = [
            'name' => str_repeat('a', 257),
            'email' => 'test@example.com',
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('phone'));
    }

    public function test_prepare_for_validation_sanitizes_phone_number(): void
    {
        // Arrange
        $input_data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'phone' => '(555) 123-4567',
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
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
            'theme' => TrooperTheme::STORMTROOPER->value,
        ];

        // Act
        $this->subject->merge($input_data);
        $this->invokeMethod($this->subject, 'prepareForValidation');

        // Assert
        $this->assertEquals('', $this->subject->input('phone'));
    }
}
