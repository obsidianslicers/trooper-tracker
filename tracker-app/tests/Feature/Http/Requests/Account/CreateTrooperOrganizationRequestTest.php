<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Http\Requests\Account\CreateTrooperOrganizationRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateTrooperOrganizationRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true(): void
    {
        $subject = new CreateTrooperOrganizationRequest;

        $this->assertTrue($subject->authorize());
    }

    public function test_rules_requires_organization_id(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $subject = $this->makeRequest($trooper);
        $rules = $subject->rules();

        $this->assertArrayHasKey('organization_id', $rules);
        $this->assertContains('required', $rules['organization_id']);
    }

    public function test_rules_validates_organization_id_required(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $subject = $this->makeRequest($trooper);

        $validator = Validator::make([], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('organization_id', $validator->errors()->toArray());
    }

    public function test_rules_rejects_child_organization_for_visitor(): void
    {
        $trooper = Trooper::factory()->asVisitor()->create();
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()->asRegion()->withParent($primary)->withNodePath('100:200:')->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->withNodePath('100:200:300:')->create();

        $subject = $this->makeRequest($trooper, [
            'organization_id' => $unit->id,
        ]);

        $validator = Validator::make([
            'organization_id' => $unit->id,
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('organization_id', $validator->errors()->toArray());
    }

    public function test_rules_allows_valid_organization_for_non_visitor(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $subject = $this->makeRequest($trooper, [
            'organization_id' => $organization->id,
        ]);

        $validator = Validator::make([
            'organization_id' => $organization->id,
        ], $subject->rules());

        $this->assertFalse($validator->fails());
    }

    public function test_rules_identifier_uses_default_max_length_when_primary_has_no_validation(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $subject = $this->makeRequest($trooper, [
            'organization_id' => $organization->id,
        ]);

        $valid = Validator::make([
            'organization_id' => $organization->id,
            'identifier' => str_repeat('A', 64),
        ], $subject->rules());

        $invalid = Validator::make([
            'organization_id' => $organization->id,
            'identifier' => str_repeat('A', 65),
        ], $subject->rules());

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('identifier', $invalid->errors()->toArray());
    }

    public function test_rules_identifier_applies_primary_club_custom_validation(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::IDENTIFIER_VALIDATION => 'regex:/^TK-[0-9]{4}$/',
        ]);
        $region = Organization::factory()->asRegion()->withParent($primary)->withNodePath('100:200:')->create();

        $subject = $this->makeRequest($trooper, [
            'organization_id' => $region->id,
        ]);

        $validator = Validator::make([
            'organization_id' => $region->id,
            'identifier' => 'BAD-1234',
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    public function test_rules_identifier_must_be_unique_within_primary_club(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $existing_trooper = Trooper::factory()->asMember()->create();
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::IDENTIFIER_VALIDATION => 'regex:/^TK-[0-9]{4}$/',
        ]);
        $region = Organization::factory()->asRegion()->withParent($primary)->withNodePath('100:200:')->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($primary)
            ->withIdentifier('TK-1234')
            ->create();

        $subject = $this->makeRequest($trooper, [
            'organization_id' => $region->id,
        ]);

        $validator = Validator::make([
            'organization_id' => $region->id,
            'identifier' => 'TK-1234',
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    public function test_rules_identifier_must_not_exist_on_pending_request_within_primary_club(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $existing_trooper = Trooper::factory()->asMember()->create();
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::IDENTIFIER_VALIDATION => 'regex:/^TK-[0-9]{4}$/',
        ]);
        $region = Organization::factory()->asRegion()->withParent($primary)->withNodePath('100:200:')->create();

        TrooperRequest::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($region)
            ->forPrimaryOrganization($primary)
            ->withIdentifier('TK-1234')
            ->create();

        $subject = $this->makeRequest($trooper, [
            'organization_id' => $region->id,
        ]);

        $validator = Validator::make([
            'organization_id' => $region->id,
            'identifier' => 'TK-1234',
        ], $subject->rules());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('identifier', $validator->errors()->toArray());
    }

    private function makeRequest(Trooper $trooper, array $payload = []): CreateTrooperOrganizationRequest
    {
        $subject = new CreateTrooperOrganizationRequest;
        $subject->setUserResolver(fn() => $trooper);
        $subject->merge($payload);

        return $subject;
    }
}
