<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Award;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Tests\TestCase;

class AwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_award(): void
    {
        $subject = Award::factory()->create();

        $this->assertInstanceOf(Award::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_troopers_relationship_returns_belongs_to_many(): void
    {
        $subject = Award::factory()->create();

        $this->assertInstanceOf(BelongsToMany::class, $subject->troopers());
    }
}