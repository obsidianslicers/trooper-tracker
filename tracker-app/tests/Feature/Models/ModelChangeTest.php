<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\ModelChange;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Tests\TestCase;

class ModelChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_model_change(): void
    {
        $subject = ModelChange::factory()->create();

        $this->assertInstanceOf(ModelChange::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_auditable_relationship_returns_morph_to(): void
    {
        $trooper = Trooper::factory()->create();

        $subject = ModelChange::factory()
            ->forTrooper($trooper)
            ->create();

        $this->assertInstanceOf(MorphTo::class, $subject->auditable());
    }

    public function test_auditable_label_uses_auditable_model_when_available(): void
    {
        $trooper = Trooper::factory()->create();

        $subject = ModelChange::factory()
            ->forTrooper($trooper)
            ->create();

        $this->assertSame($trooper->getAuditLabel(), $subject->fresh()->auditable_label);
    }
}