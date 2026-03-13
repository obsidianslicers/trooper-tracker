<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Auth;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
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
