<?php

declare(strict_types=1);

namespace Tests\Feature\Mail\Events;

use App\Enums\TroopCorrectionStatus;
use App\Mail\Events\TroopCorrectionDecided;
use App\Mail\Events\TroopCorrectionRequested;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\BuildsTroopCorrections;
use Tests\TestCase;

class TroopCorrectionMailTest extends TestCase
{
    use BuildsTroopCorrections;
    use RefreshDatabase;

    public function test_request_mail_asks_the_coordinator_with_a_link_to_answer(): void
    {
        Notification::fake();
        config(['mail.prefix' => '[TEST]']);
        $trooper = Trooper::factory()->withDisplayName('TK-1138')->create();
        $correction = $this->requestCorrection($trooper, $this->completedShift(Trooper::factory()->create())->event);

        $mail = new TroopCorrectionRequested($correction);
        $html = $mail->render();

        $this->assertSame('[TEST] Troop Credit Request', $mail->envelope()->subject);
        $this->assertStringContainsString('TK-1138', $html);
        $this->assertStringContainsString('I held the door for the parade.', $html);
        $this->assertStringContainsString(route('events.troop-correction', $correction), $html);
    }

    public function test_reminder_mail_says_when_moderators_take_over(): void
    {
        Notification::fake();
        $correction = $this->requestCorrection(Trooper::factory()->create(), $this->completedShift(Trooper::factory()->create())->event);

        $mail = new TroopCorrectionRequested($correction, is_reminder: true);

        $this->assertStringContainsString('Reminder', $mail->envelope()->subject);
        $this->assertStringContainsString('7 days', $mail->render());
    }

    public function test_decided_mail_says_whether_it_was_approved(): void
    {
        Notification::fake();
        $correction = $this->requestCorrection(Trooper::factory()->create(), $this->completedShift(Trooper::factory()->create())->event);

        $correction->status = TroopCorrectionStatus::APPROVED;
        $this->assertStringContainsString('was approved', (new TroopCorrectionDecided($correction))->render());

        $correction->status = TroopCorrectionStatus::DENIED;
        $this->assertStringContainsString('was denied', (new TroopCorrectionDecided($correction))->render());
    }
}
