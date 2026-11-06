<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Resources;

use App\Messages\Troopers\Resources\PendingTrooperRequestsCollection;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PendingTrooperRequestsCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_includes_organizations_and_trooper_contact(): void
    {
        $trooper = Trooper::factory()->withDisplayName('TK-421')->create();
        $primary = Organization::factory()->asOrganization()->withName('501st')->create();
        $region = Organization::factory()->asRegion()->withParent($primary)
            ->withName('Florida')->create();
        $request = TrooperRequest::factory()->forTrooper($trooper)->forOrganization($region)
            ->forPrimaryOrganization($primary)->withIdentifier('421')->asPending()->create();

        $subject = new PendingTrooperRequestsCollection(collect([$request]));
        $result = $subject->toArray(new Request)[0];

        $this->assertSame($request->id, $result['id']);
        $this->assertSame('421', $result['identifier']);
        $this->assertSame(
            ['id' => $region->id, 'name' => 'Florida', 'parent_name' => '501st'],
            $result['organization']
        );
        $this->assertSame(
            ['id' => $primary->id, 'name' => '501st', 'parent_name' => null],
            $result['primary_organization']
        );
        $this->assertSame($trooper->id, $result['trooper']['id']);
        $this->assertSame('TK-421', $result['trooper']['display_name']);
        $this->assertSame($trooper->email, $result['trooper']['email']);
    }
}
