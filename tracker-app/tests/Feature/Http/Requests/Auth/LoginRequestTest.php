<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Auth;

use App\Http\Requests\Auth\LoginRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LoginRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new LoginRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_email_field(): void
    {
        $subject = new LoginRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::EMAIL, $rules);
        $this->assertContains('required', $rules[Trooper::EMAIL]);
    }

    public function test_rules_requires_password_field(): void
    {
        $subject = new LoginRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Trooper::PASSWORD, $rules);
        $this->assertContains('required', $rules[Trooper::PASSWORD]);
    }

    public function test_rules_validates_email_exists_in_database(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $subject = new LoginRequest;

        $validator = Validator::make(
            [
                Trooper::EMAIL => $trooper->email,
                Trooper::PASSWORD => 'password',
            ],
            $subject->rules(),
            $subject->messages()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_fails_when_email_does_not_exist(): void
    {
        $subject = new LoginRequest;

        $validator = Validator::make(
            [
                Trooper::EMAIL => 'nonexistent@example.com',
                Trooper::PASSWORD => 'password',
            ],
            $subject->rules(),
            $subject->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_fails_when_email_is_missing(): void
    {
        $subject = new LoginRequest;

        $validator = Validator::make(
            [Trooper::PASSWORD => 'password'],
            $subject->rules(),
            $subject->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::EMAIL, $validator->errors()->toArray());
    }

    public function test_rules_fails_when_password_is_missing(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $subject = new LoginRequest;

        $validator = Validator::make(
            [Trooper::EMAIL => $trooper->email],
            $subject->rules(),
            $subject->messages()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Trooper::PASSWORD, $validator->errors()->toArray());
    }

    public function test_messages_provides_custom_error_for_email_required(): void
    {
        $subject = new LoginRequest;
        $messages = $subject->messages();

        $this->assertArrayHasKey(Trooper::EMAIL . '.required', $messages);
        $this->assertSame('Email is required.', $messages[Trooper::EMAIL . '.required']);
    }

    public function test_messages_provides_custom_error_for_email_exists(): void
    {
        $subject = new LoginRequest;
        $messages = $subject->messages();

        $this->assertArrayHasKey(Trooper::EMAIL . '.exists', $messages);
        $this->assertStringContainsString('setup your account', $messages[Trooper::EMAIL . '.exists']);
    }

    public function test_messages_provides_custom_error_for_password_required(): void
    {
        $subject = new LoginRequest;
        $messages = $subject->messages();

        $this->assertArrayHasKey(Trooper::PASSWORD . '.required', $messages);
        $this->assertSame('Password is required.', $messages[Trooper::PASSWORD . '.required']);
    }
}
