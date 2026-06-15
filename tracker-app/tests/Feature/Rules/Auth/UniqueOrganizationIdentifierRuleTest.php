<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Auth;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UniqueOrganizationIdentifierRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_identifier_exists_for_another_trooper_in_same_organization(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        TrooperOrganization::factory()
            ->forOrganization($organization)
            ->withIdentifier('TK-421')
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-421',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Florida Garrison TKID already exists.',
            $validator->errors()->first('identifier')
        );
    }

    public function test_passes_when_identifier_does_not_exist_in_organization(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        TrooperOrganization::factory()
            ->forOrganization($organization)
            ->withIdentifier('TK-421')
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-422',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_passes_for_existing_identifier_when_updating_same_trooper(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        $trooper = Trooper::factory()->create();

        TrooperOrganization::factory()
            ->forOrganization($organization)
            ->forTrooper($trooper)
            ->withIdentifier('TK-421')
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-421',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization, $trooper)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_identifier_exists_on_pending_request_for_another_trooper(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->create())
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-421')
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-421',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Florida Garrison TKID already exists.',
            $validator->errors()->first('identifier')
        );
    }

    public function test_passes_for_pending_request_identifier_when_updating_same_trooper(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        $trooper = Trooper::factory()->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-421')
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-421',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization, $trooper)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_passes_for_pending_request_identifier_in_different_primary_club(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();
        $other_organization = Organization::factory()
            ->withName('Rebel Legion')
            ->withIdentifierDisplay('RLID')
            ->create();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->create())
            ->forOrganization($other_organization)
            ->forPrimaryOrganization($other_organization)
            ->withIdentifier('TK-421')
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-421',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_passes_for_identifier_on_denied_request(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->create())
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('TK-421')
            ->asDenied()
            ->create();

        $validator = Validator::make([
            'identifier' => 'TK-421',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_passes_when_identifier_is_empty(): void
    {
        $organization = Organization::factory()
            ->withName('Florida Garrison')
            ->withIdentifierDisplay('TKID')
            ->create();

        $validator = Validator::make([
            'identifier' => '',
        ], [
            'identifier' => [new UniqueOrganizationIdentifierRule($organization)],
        ]);

        $this->assertTrue($validator->passes());
    }
}
