<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Awards\Commands;

use App\Enums\AwardFrequency;
use App\Messages\Awards\Commands\CreateAward;
use App\Messages\Awards\Commands\RenameAward;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateAwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_saves_the_award_and_rename_changes_only_its_name(): void
    {
        $organization = Organization::factory()->create();

        $award = (new CreateAward($organization->id, 'Trooper of the Year', AwardFrequency::ANNUALLY))->handle();
        (new RenameAward($award, 'Trooper of the Year 2026'))->handle();

        $award->refresh();
        $this->assertSame('Trooper of the Year 2026', $award->name);
        $this->assertSame(AwardFrequency::ANNUALLY, $award->frequency);
        $this->assertSame($organization->id, $award->organization_id);
    }
}
