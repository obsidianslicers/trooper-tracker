<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Resources;

use App\Messages\Troopers\Resources\PendingTrooperApprovalsCollection;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class PendingTrooperApprovalsCollectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_to_array_includes_trooper_and_requests_without_guardian_for_adult(): void
    {
        $trooper = Trooper::factory()->asPending()
            ->state([Trooper::DATE_OF_BIRTH => now()->subYears(30)])->create();
        $primary = Organization::factory()->asOrganization()->withName('501st')->create();
        $unit = Organization::factory()->asRegion()->withParent($primary)->withName('Florida')->create();
        $request = TrooperRequest::factory()->forTrooper($trooper)->forOrganization($unit)
            ->forPrimaryOrganization($primary)->withIdentifier('1138')->asPending()->create();

        $result = $this->transform($trooper);

        $this->assertSame($trooper->id, $result['trooper_id']);
        $this->assertFalse($result['is_minor']);
        $this->assertNull($result['guardian']);
        $this->assertNull($result['visitor_expires_diff_for_humans']);
        $this->assertSame([[
            'id' => $request->id,
            'identifier' => '1138',
            'organization' => ['name' => 'Florida', 'parent_name' => '501st'],
            'primary_organization' => [
                'requires_guardian' => $primary->requires_guardian,
                'name' => '501st',
            ],
        ]], $result['trooper_requests']);
    }

    public function test_to_array_includes_guardian_contact_for_minor(): void
    {
        $guardian = Trooper::factory()->withDisplayName('Parent')->create();
        $minor = Trooper::factory()->asPending()->withGuardian($guardian)
            ->state([Trooper::DATE_OF_BIRTH => now()->subYears(12)])->create();

        $result = $this->transform($minor);

        $this->assertTrue($result['is_minor']);
        $this->assertSame($guardian->id, $result['guardian']['trooper_id']);
        $this->assertSame('Parent', $result['guardian']['display_name']);
    }

    public function test_to_array_returns_null_guardian_for_minor_without_one(): void
    {
        $minor = Trooper::factory()->asPending()
            ->state([Trooper::DATE_OF_BIRTH => now()->subYears(12)])->create();

        $result = $this->transform($minor);

        $this->assertTrue($result['is_minor']);
        $this->assertNull($result['guardian']);
    }

    public function test_to_array_describes_visitor_expiry(): void
    {
        $visitor = Trooper::factory()->asVisitor()
            ->state([Trooper::VISITOR_EXPIRES_AT => now()->addDays(3)])->create();

        $result = $this->transform($visitor);

        $this->assertNotNull($result['visitor_expires_diff_for_humans']);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(Trooper $trooper): array
    {
        $subject = new PendingTrooperApprovalsCollection(collect([$trooper->fresh()]));

        return $subject->toArray(new Request)[0];
    }
}
