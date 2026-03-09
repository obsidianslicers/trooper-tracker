<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Enums\TrooperTheme;
use App\Http\Requests\Account\ProfileRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProfileRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new ProfileRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_legal_name(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::LEGAL_NAME]);
    }

    public function test_rules_requires_display_name(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::DISPLAY_NAME, $rules);
        $this->assertContains('required', $rules[Trooper::DISPLAY_NAME]);
    }

    public function test_rules_requires_email(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::EMAIL, $rules);
        $this->assertContains('required', $rules[Trooper::EMAIL]);
        $this->assertContains('email', $rules[Trooper::EMAIL]);
    }

    public function test_rules_validates_email_format(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'not-an-email',
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_phone_is_nullable(): void
    {
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::PHONE, $rules);
        $this->assertContains('nullable', $rules[Trooper::PHONE]);
    }

    public function test_rules_phone_has_max_length(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
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
        $subject = new ProfileRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::THEME, $rules);
        $this->assertContains('required', $rules[Trooper::THEME]);
    }

    public function test_rules_validates_theme_is_valid_enum(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::THEME => 'invalid-theme',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::THEME, $validator->errors()->toArray());
    }

    public function test_rules_accepts_light_theme(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accepts_dark_theme(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::THEME => TrooperTheme::CLONE ->value,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_validates_legal_name_max_length(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => str_repeat('a', 257),
                Trooper::DISPLAY_NAME => 'Tester',
                Trooper::EMAIL => 'test@example.com',
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::LEGAL_NAME, $validator->errors()->toArray());
    }

    public function test_rules_validates_display_name_max_length(): void
    {
        $subject = new ProfileRequest;

        $validator = Validator::make(
            [
                Trooper::LEGAL_NAME => 'Test Trooper',
                Trooper::DISPLAY_NAME => str_repeat('a', 257),
                Trooper::EMAIL => 'test@example.com',
                Trooper::THEME => TrooperTheme::STORMTROOPER->value,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::DISPLAY_NAME, $validator->errors()->toArray());
    }
}
