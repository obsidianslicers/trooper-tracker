<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Pivots;

use App\Models\Award;
use App\Models\Pivots\AwardTrooperPivot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AwardTrooperPivotTest extends TestCase
{
    use RefreshDatabase;

    public function test_award_troopers_relationship_uses_custom_pivot(): void
    {
        $subject = Award::factory()->create();

        $this->assertSame(AwardTrooperPivot::class, $subject->troopers()->getPivotClass());
    }
}