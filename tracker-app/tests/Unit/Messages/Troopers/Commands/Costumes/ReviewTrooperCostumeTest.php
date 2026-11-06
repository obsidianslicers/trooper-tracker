<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Costumes;

use App\Messages\Troopers\Commands\Costumes\ApproveTrooperCostume;
use App\Messages\Troopers\Commands\Costumes\DenyTrooperCostume;
use App\Messages\Troopers\Commands\Costumes\RevokeTrooperCostume;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\Concerns\BuildsCostumeReviews;
use Tests\TestCase;

class ReviewTrooperCostumeTest extends TestCase
{
    use BuildsCostumeReviews;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpClubs();
    }

    public function test_approve_records_the_officer(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $pending = $this->costumeFor($this->memberOf($this->garrison), $this->club);

        $subject = new ApproveTrooperCostume($moderator, $pending);
        $subject->handle();

        $pending->refresh();
        $this->assertFalse($pending->isPending());
        $this->assertSame($moderator->id, $pending->approved_id);
    }

    public function test_approve_refuses_an_approved_costume(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);

        $this->expectException(RuntimeException::class);

        (new ApproveTrooperCostume($moderator, $approved))->handle();
    }

    public function test_deny_removes_the_request(): void
    {
        $pending = $this->costumeFor($this->memberOf($this->garrison), $this->club);

        (new DenyTrooperCostume($pending))->handle();

        $this->assertSoftDeleted('tt_trooper_costumes', [TrooperCostume::ID => $pending->id]);
    }

    public function test_revoke_removes_the_costume_and_clears_the_approval(): void
    {
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);

        (new RevokeTrooperCostume($approved))->handle();

        $revoked = TrooperCostume::withPending()->withTrashed()->find($approved->id);
        $this->assertTrue($revoked->trashed());
        $this->assertTrue($revoked->isPending());
    }
}
