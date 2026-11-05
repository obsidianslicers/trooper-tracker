<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\Resources;

use App\Enums\TrooperTheme;
use App\Messages\Account\Resources\TrooperDetails;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrooperDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_returns_profile_fields_and_theme_options(): void
    {
        $trooper = Trooper::factory()->withDisplayName('Darth Vader')->withLegalName('Anakin')->create();

        $result = (new TrooperDetails($trooper))->toArray(new Request);

        $this->assertSame('Darth Vader', $result[Trooper::DISPLAY_NAME]);
        $this->assertSame('Anakin', $result[Trooper::LEGAL_NAME]);
        $this->assertSame([], $result['display_costumes']);
        $this->assertSame(TrooperTheme::toValueLabels(), $result['theme_enums']);
    }

    public function test_to_array_labels_prefixed_costumes_with_identifier_and_organization(): void
    {
        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->withName('501st')->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)
            ->withIdentifier('1138')->create();

        $prefixed = OrganizationCostume::factory()->forOrganization($organization)
            ->forCostume(Costume::factory()->withName('Stormtrooper')->create())
            ->withPrefix('TK')->create();
        $unprefixed = OrganizationCostume::factory()->forOrganization($organization)
            ->state([OrganizationCostume::PREFIX => null])->create();

        $trooper_costume = TrooperCostume::factory()->forTrooper($trooper)
            ->forOrganizationCostume($prefixed)->create();
        TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($unprefixed)->create();

        $result = (new TrooperDetails($trooper))->toArray(new Request);

        $this->assertSame(
            [['value' => $trooper_costume->id, 'label' => 'TK1138 — Stormtrooper (501st)']],
            $result['display_costumes']
        );
    }
}
