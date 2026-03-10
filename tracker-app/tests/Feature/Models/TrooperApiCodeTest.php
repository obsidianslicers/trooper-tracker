<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\TrooperApiCode;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperApiCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper_api_code(): void
    {
        $trooper = Trooper::factory()->create();

        $subject = TrooperApiCode::factory()
            ->forTrooper($trooper)
            ->make();

        $this->assertInstanceOf(TrooperApiCode::class, $subject);
        $this->assertSame($trooper->id, $subject->{TrooperApiCode::TROOPERID});
        $this->assertNotEmpty($subject->{TrooperApiCode::API_CODE});
        $this->assertFalse($subject->exists);
    }
}