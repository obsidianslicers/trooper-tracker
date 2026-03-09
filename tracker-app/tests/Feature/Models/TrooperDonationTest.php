<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\TrooperDonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperDonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_trooper_donation(): void
    {
        $subject = TrooperDonation::factory()->create();

        $this->assertInstanceOf(TrooperDonation::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}