<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Costume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class CostumeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_costume(): void
    {
        $subject = Costume::factory()->create();

        $this->assertInstanceOf(Costume::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_organization_costumes_relationship_returns_has_many(): void
    {
        $subject = Costume::factory()->create();

        $this->assertInstanceOf(HasMany::class, $subject->organization_costumes());
    }
}