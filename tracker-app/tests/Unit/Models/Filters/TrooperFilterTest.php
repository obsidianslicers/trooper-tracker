<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Enums\MembershipRole;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrooperFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_by_membership_role(): void
    {
        $active_trooper = Trooper::factory()->asAdministrator()->create();
        Trooper::factory()->asModerator()->create();

        $request = new Request(['membership_role' => MembershipRole::ADMINISTRATOR->value]);
        $subject = new TrooperFilter($request);

        $query = $subject->apply(Trooper::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($active_trooper->id, $query->first()->id);
    }

    public function test_it_can_filter_by_search_term(): void
    {
        $trooper_to_find = Trooper::factory()->create(['name' => 'John Doe']);
        Trooper::factory()->create(['name' => 'Jane Smith']);

        $request = new Request(['search_term' => 'John']);
        $subject = new TrooperFilter($request);

        $query = $subject->apply(Trooper::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($trooper_to_find->id, $query->first()->id);
    }

    public function test_it_ignores_search_term_if_too_short(): void
    {
        Trooper::factory()->create(['name' => 'John Doe']);
        Trooper::factory()->create(['name' => 'Jane Smith']);

        $request = new Request(['search_term' => 'Jo']);
        $subject = new TrooperFilter($request);

        $query = $subject->apply(Trooper::query());

        $this->assertEquals(2, $query->count());
    }

    public function test_it_can_apply_multiple_filters(): void
    {
        // The trooper we expect to find
        $matching_trooper = Trooper::factory()->create([
            'membership_role' => MembershipRole::ADMINISTRATOR,
            'name' => 'Find This Active Trooper',
        ]);

        // Decoys
        Trooper::factory()->create([
            'membership_role' => MembershipRole::MEMBER,
            'name' => 'Do Not Find This Reserve Trooper',
        ]);
        Trooper::factory()->create([
            'membership_role' => MembershipRole::ADMINISTRATOR,
            'name' => 'Another Active Trooper',
        ]);

        $request = new Request([
            'membership_role' => MembershipRole::ADMINISTRATOR->value,
            'search_term' => 'Find This',
        ]);
        $subject = new TrooperFilter($request);

        $query = $subject->apply(Trooper::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($matching_trooper->id, $query->first()->id);
    }
}
