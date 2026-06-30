<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Admin\Troopers;

use App\Mail\Admin\Troopers\TrooperMilestoneMail;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrooperMilestoneMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $achievement = $this->createAchievementForMemberTrooper();
        $mail = new TrooperMilestoneMail($achievement);

        $this->assertSame('[TEST] Trooper Milestone Achieved', $mail->envelope()->subject);
    }

    public function test_content_contains_expected_view_and_achievement(): void
    {
        $achievement = $this->createAchievementForMemberTrooper();

        $mail = new TrooperMilestoneMail($achievement);
        $content = $mail->content();
        $html = $mail->render();

        $this->assertSame('emails.admin.troopers.trooper-milestone', $content->view);
        $this->assertSame($achievement, $mail->achievement);
        $this->assertStringContainsString($achievement->trooper->legal_name, $html);
        $this->assertStringContainsString($achievement->trooper->display_name, $html);
        $this->assertStringContainsString('Imperial Personnel Registry', $html);
        $this->assertStringContainsString('Test Garrison', $html);
        $this->assertSame([], $mail->attachments());
    }

    public function test_mail_implements_should_queue(): void
    {
        $achievement = $this->createAchievementForMemberTrooper();

        $mail = new TrooperMilestoneMail($achievement);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
    }

    private function createAchievementForMemberTrooper(): TrooperAchievement
    {
        $organization = Organization::factory()
            ->withName('Test Garrison')
            ->create();
        $trooper = Trooper::factory()->asMember()->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        return TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->create();
    }
}
