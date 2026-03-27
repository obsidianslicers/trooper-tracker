<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Auth;

use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class RegisterRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new RegisterRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_legal_name(): void
    {
        $subject = new RegisterRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('legal_name', $rules);
        $this->assertContains('required', $rules['legal_name']);
        $this->assertContains('string', $rules['legal_name']);
    }

    public function test_rules_requires_display_name(): void
    {
        $subject = new RegisterRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('display_name', $rules);
        $this->assertContains('required', $rules['display_name']);
    }

    public function test_rules_requires_unique_email(): void
    {
        $existing_trooper = Trooper::factory()->asMember()->create();
        $subject = new RegisterRequest;

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => $existing_trooper->email,
                'account_type' => 'member',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_rules_requires_account_type(): void
    {
        $subject = new RegisterRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('account_type', $rules);
        $this->assertContains('required', $rules['account_type']);
    }

    public function test_rules_validates_account_type_is_member_or_handler(): void
    {
        $subject = new RegisterRequest;

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => 'test@example.com',
                'account_type' => 'invalid',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('account_type', $validator->errors()->toArray());
    }

    public function test_rules_allows_member_account_type(): void
    {
        $subject = new RegisterRequest;

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => 'test@example.com',
                'account_type' => 'member',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->errors()->has('account_type'));
    }

    public function test_rules_allows_handler_account_type(): void
    {
        $subject = new RegisterRequest;

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => 'test@example.com',
                'account_type' => 'handler',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->errors()->has('account_type'));
    }

    public function test_rules_does_not_require_password_without_registration_auth(): void
    {
        $subject = new RegisterRequest;
        $rules = $subject->rules();

        $this->assertArrayNotHasKey('password', $rules);
    }

    public function test_rules_requires_password_when_email_authentication_method(): void
    {
        Session::put('registration_auth', [
            'method' => 'email',
            'email' => 'test@example.com',
        ]);

        $subject = new RegisterRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('password', $rules);
        $this->assertContains('required', $rules['password']);
    }

    public function test_rules_phone_is_optional(): void
    {
        $subject = new RegisterRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('phone', $rules);
        $this->assertContains('nullable', $rules['phone']);
    }

    public function test_rules_phone_has_max_length(): void
    {
        $subject = new RegisterRequest;

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => 'test@example.com',
                'account_type' => 'member',
                'phone' => '12345678901234567',  // 17 digits, exceeds max of 16
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('phone', $validator->errors()->toArray());
    }

    public function test_rules_validates_email_format(): void
    {
        $subject = new RegisterRequest;

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => 'not-an-email',
                'account_type' => 'member',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_rules_validates_email_max_length(): void
    {
        $subject = new RegisterRequest;

        $long_email = str_repeat('a', 250) . '@example.com';

        $validator = Validator::make(
            [
                'legal_name' => 'Test Trooper',
                'display_name' => 'Tester',
                'email' => $long_email,
                'account_type' => 'member',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function test_rules_requires_guardian_email_when_selected_organization_requires_guardian(): void
    {
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'),
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make(
            $data,
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('guardian_email', $validator->errors()->toArray());
    }

    public function test_rules_does_not_require_guardian_email_when_selected_org_does_not_require_guardian(): void
    {
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => false,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'date_of_birth' => now()->subYears(17)->format('Y-m-d'),
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make(
            $data,
            $subject->rules()
        );

        $this->assertFalse($validator->errors()->has('guardian_email'));
    }

    public function test_rules_requires_date_of_birth_when_selected_org_requires_guardian(): void
    {
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date_of_birth', $validator->errors()->toArray());
    }

    public function test_rules_does_not_require_date_of_birth_when_selected_org_does_not_require_guardian(): void
    {
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => false,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertFalse($validator->errors()->has('date_of_birth'));
    }

    public function test_rules_rejects_date_of_birth_when_18_years_old_for_guardian_required_org(): void
    {
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'date_of_birth' => now()->subYears(18)->format('Y-m-d'),
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date_of_birth', $validator->errors()->toArray());
    }

    public function test_rules_rejects_date_of_birth_when_13_years_old_for_guardian_required_org(): void
    {
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'date_of_birth' => now()->subYears(13)->format('Y-m-d'),
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date_of_birth', $validator->errors()->toArray());
    }

    public function test_rules_allows_date_of_birth_between_13_and_18_for_guardian_required_org(): void
    {
        $guardian = Trooper::factory()->asMember()->create();
        $minor_trooper = Trooper::factory()
            ->asMember()
            ->state([Trooper::GUARDIAN_ID => $guardian->id])
            ->create();
        $organization = Organization::factory()->create([
            Organization::REQUIRES_GUARDIAN => true,
        ]);

        $subject = new RegisterRequest;
        $data = [
            'legal_name' => 'Test Trooper',
            'display_name' => 'Tester',
            'email' => 'test@example.com',
            'account_type' => 'member',
            'date_of_birth' => now()->subYears(16)->format('Y-m-d'),
            'guardian_email' => $minor_trooper->email,
            'organizations' => [
                (string) $organization->id => ['selected' => '1'],
            ],
        ];

        $subject->merge($data);

        $validator = Validator::make($data, $subject->rules());

        $this->assertFalse($validator->errors()->has('date_of_birth'));
    }
}
