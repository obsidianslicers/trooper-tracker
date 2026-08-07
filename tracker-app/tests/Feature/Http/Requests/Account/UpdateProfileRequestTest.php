<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Enums\TrooperTheme;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new UpdateProfileRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_legal_name(): void
    {
        $subject = new UpdateProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::LEGAL_NAME]);
    }

    public function test_rules_requires_display_name(): void
    {
        $subject = new UpdateProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::DISPLAY_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::DISPLAY_NAME]);
    }

    public function test_rules_phone_is_nullable(): void
    {
        $subject = new UpdateProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::PHONE, $rules);
        $this->assertContains('nullable', $rules[Trooper::PHONE]);
    }

    public function test_rules_phone_has_max_length(): void
    {
        $subject = new UpdateProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::PHONE => '12345678901234567', // 17 chars, max is 16
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::PHONE, $validator->errors()->toArray());
    }

    public function test_rules_requires_theme(): void
    {
        $subject = new UpdateProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::THEME, $rules);
        $this->assertContains('required', $rules[Trooper::THEME]);
    }

    public function test_rules_validates_theme_is_valid_enum(): void
    {
        $subject = new UpdateProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::THEME => 'invalid-theme',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::THEME, $validator->errors()->toArray());
    }

    public function test_rules_accepts_light_theme(): void
    {
        $subject = new UpdateProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_dark_theme(): void
    {
        $subject = new UpdateProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::THEME => TrooperTheme::CLONE ->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_legal_name_max_length(): void
    {
        $subject = new UpdateProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => str_repeat('a', 257),
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $validator->errors()->toArray());
    }

    public function test_rules_validates_display_name_max_length(): void
    {
        $subject = new UpdateProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => str_repeat('a', 257),
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::DISPLAY_NAME, $validator->errors()->toArray());
    }
}
