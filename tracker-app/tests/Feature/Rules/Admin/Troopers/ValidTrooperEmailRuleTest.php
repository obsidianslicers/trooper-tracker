<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Troopers;

use App\Models\Trooper;
use App\Rules\Admin\Troopers\ValidTrooperEmailRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ValidTrooperEmailRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_no_trooper_and_email_is_invalid(): void
    {
        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => [new ValidTrooperEmailRule]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The email field must be a valid email address.',
            $validator->errors()->first('email')
        );
    }

    public function test_passes_when_no_trooper_and_email_is_valid(): void
    {
        $validator = Validator::make(
            ['email' => 'valid@example.com'],
            ['email' => [new ValidTrooperEmailRule]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_trooper_present_and_new_email_is_invalid(): void
    {
        $trooper = Trooper::factory()->withEmail('trooper@example.com')->create();

        $validator = Validator::make(
            ['email' => 'not-an-email'],
            ['email' => [new ValidTrooperEmailRule($trooper)]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_passes_when_submitted_email_matches_troopers_current_placeholder_email(): void
    {
        $trooper = Trooper::factory()->withEmail('^' . uniqid())->create();

        $validator = Validator::make(
            ['email' => $trooper->email],
            ['email' => [new ValidTrooperEmailRule($trooper)]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_placeholder_email_is_changed_to_something_still_invalid(): void
    {
        $trooper = Trooper::factory()->withEmail('^' . uniqid())->create();

        $validator = Validator::make(
            ['email' => 'still-not-an-email'],
            ['email' => [new ValidTrooperEmailRule($trooper)]]
        );

        $this->assertTrue($validator->fails());
    }

    public function test_passes_when_placeholder_email_is_changed_to_a_valid_email(): void
    {
        $trooper = Trooper::factory()->withEmail('^' . uniqid())->create();

        $validator = Validator::make(
            ['email' => 'valid@example.com'],
            ['email' => [new ValidTrooperEmailRule($trooper)]]
        );

        $this->assertTrue($validator->passes());
    }
}
