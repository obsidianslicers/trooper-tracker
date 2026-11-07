<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Costumes;

use App\Messages\Troopers\Commands\Costumes\ApproveTrooperCostume;
use App\Messages\Troopers\Commands\Costumes\DenyTrooperCostume;
use App\Messages\Troopers\Commands\Costumes\RevokeTrooperCostume;
use App\Messages\Troopers\Commands\Costumes\UndoRevokeTrooperCostume;
use App\Models\TrooperCostume;
use App\Notifications\Troopers\CostumeApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
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

    public function test_approve_notifies_the_trooper(): void
    {
        Notification::fake();
        $moderator = $this->moderatorOf($this->garrison);
        $trooper = $this->memberOf($this->garrison);
        $pending = $this->costumeFor($trooper, $this->club);

        (new ApproveTrooperCostume($moderator, $pending))->handle();

        Notification::assertSentTo(
            $trooper,
            CostumeApprovedNotification::class,
            fn (CostumeApprovedNotification $notification): bool => $notification->costume_name === 'Emperor Palpatine'
                && $notification->club_name === '501st Legion'
        );
    }

    public function test_approved_notification_renders_mail_and_database_payload(): void
    {
        $trooper = $this->memberOf($this->garrison);

        $subject = new CostumeApprovedNotification(
            costume_name: 'Emperor Palpatine',
            club_name: '501st Legion',
        );

        $html = $subject->toMail($trooper)->render();
        $payload = $subject->toArray($trooper);

        $this->assertStringContainsString('Emperor Palpatine', $html);
        $this->assertStringContainsString('501st Legion', $html);
        $this->assertSame('Costume Approved', $payload['title']);
        $this->assertSame('Emperor Palpatine is approved for 501st Legion.', $payload['body']);
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
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);

        (new RevokeTrooperCostume($moderator, $approved))->handle();

        $revoked = TrooperCostume::withPending()->withTrashed()->find($approved->id);
        $this->assertTrue($revoked->trashed());
        $this->assertTrue($revoked->isPending());
        $this->assertTrue($revoked->isRevoked());
        $this->assertSame($moderator->id, $revoked->revoked_id);
    }

    public function test_undo_revoke_restores_and_approves_the_costume(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);
        (new RevokeTrooperCostume($moderator, $approved))->handle();
        $revoked = TrooperCostume::withPending()->withTrashed()->findOrFail($approved->id);

        (new UndoRevokeTrooperCostume($moderator, $revoked))->handle();

        $restored = TrooperCostume::findOrFail($approved->id);
        $this->assertFalse($restored->isPending());
        $this->assertFalse($restored->isRevoked());
        $this->assertSame($moderator->id, $restored->approved_id);
    }

    public function test_undo_revoke_refuses_a_costume_that_was_not_revoked(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);

        $this->expectException(RuntimeException::class);

        (new UndoRevokeTrooperCostume($moderator, $approved))->handle();
    }

    public function test_approving_a_re_added_revoked_costume_clears_the_revoke(): void
    {
        $moderator = $this->moderatorOf($this->garrison);
        $approved = $this->costumeFor($this->memberOf($this->garrison), $this->club, pending: false);
        $approved->markRevoked($moderator);
        $approved->save();

        (new ApproveTrooperCostume($moderator, $approved))->handle();

        $this->assertFalse($approved->refresh()->isRevoked());
    }
}
