<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetTrooperMinors;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperMinorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_minors_sorted_by_display_name_for_guardian(): void
    {
        $guardian = Trooper::factory()->create();

        $zulu_minor = Trooper::factory()
            ->withGuardian($guardian)
            ->withDisplayName('Zulu Minor')
            ->create();
        $alpha_minor = Trooper::factory()
            ->withGuardian($guardian)
            ->withDisplayName('Alpha Minor')
            ->create();

        Trooper::factory()
            ->withGuardian(Trooper::factory()->create())
            ->withDisplayName('Other Guardian Minor')
            ->create();

        Trooper::factory()->withDisplayName('No Guardian Minor')->create();

        $subject = new GetTrooperMinors($guardian);

        $result = $subject->handle();

        $this->assertCount(2, $result);
        $this->assertSame(
            ['Alpha Minor', 'Zulu Minor'],
            $result->pluck(Trooper::DISPLAY_NAME)->all(),
        );
        $this->assertTrue($result->contains(fn(Trooper $candidate): bool => $candidate->is($alpha_minor)));
        $this->assertTrue($result->contains(fn(Trooper $candidate): bool => $candidate->is($zulu_minor)));
        $this->assertFalse($result->contains(fn(Trooper $candidate): bool => $candidate->{Trooper::DISPLAY_NAME} === 'Other Guardian Minor'));
        $this->assertFalse($result->contains(fn(Trooper $candidate): bool => $candidate->{Trooper::DISPLAY_NAME} === 'No Guardian Minor'));
    }
}
