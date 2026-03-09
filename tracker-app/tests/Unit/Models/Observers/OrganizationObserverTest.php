<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Models\Observers\OrganizationObserver;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saved_sets_node_path_and_depth_from_hierarchy(): void
    {
        $parent = Organization::factory()->asOrganization()->create();
        $child = Organization::factory()->asRegion()->withParent($parent)->create();

        $subject = new OrganizationObserver();

        $subject->saved($child);

        $child->refresh();

        $this->assertSame($parent->id . ':' . $child->id . ':', $child->{Organization::NODE_PATH});
        $this->assertSame(2, $child->{Organization::DEPTH});
    }
}