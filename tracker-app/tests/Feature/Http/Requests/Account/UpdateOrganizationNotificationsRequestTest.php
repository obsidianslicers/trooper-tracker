<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Http\Requests\Account\UpdateOrganizationNotificationsRequest;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateOrganizationNotificationsRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new UpdateOrganizationNotificationsRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_organization_ids_array(): void
    {
        $subject = new UpdateOrganizationNotificationsRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('organization_ids', $rules);
        $this->assertContains('required', $rules['organization_ids']);
        $this->assertContains('array', $rules['organization_ids']);
    }

    public function test_rules_requires_enabled_boolean(): void
    {
        $subject = new UpdateOrganizationNotificationsRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('enabled', $rules);
        $this->assertContains('required', $rules['enabled']);
        $this->assertContains('boolean', $rules['enabled']);
    }

    public function test_rules_rejects_non_existing_organization_ids(): void
    {
        $subject = new UpdateOrganizationNotificationsRequest;

        $validator = Validator::make([
            'organization_ids' => [999999],
            'enabled' => true,
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('organization_ids.0', $validator->errors()->toArray());
    }

    public function test_rules_accepts_valid_payload(): void
    {
        $subject = new UpdateOrganizationNotificationsRequest;

        $organization = Organization::factory()->create();

        $validator = Validator::make([
            'organization_ids' => [$organization->{Organization::ID}],
            'enabled' => true,
        ], $subject->rules());

        $this->assertFalse($validator->fails());
    }
}
