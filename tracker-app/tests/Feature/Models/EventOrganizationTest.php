<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EventOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Tests\TestCase;

class EventOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_organization(): void
    {
        $subject = EventOrganization::factory()->create();

        $this->assertInstanceOf(EventOrganization::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_troopers_relationship_returns_has_many_through(): void
    {
        $subject = EventOrganization::factory()->create();

        $this->assertInstanceOf(HasManyThrough::class, $subject->troopers());
    }
}