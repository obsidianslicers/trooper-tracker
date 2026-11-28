<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Mail\HasRetryPolicy;
use App\Models\TroopCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Asks an event coordinator to confirm that a trooper who never signed up was at their event.
 */
class TroopCorrectionRequested extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(
        private readonly TroopCorrection $troop_correction,
        private readonly bool $is_reminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->is_reminder
            ? __('emails.Reminder: Troop Credit Request')
            : __('emails.Troop Credit Request');

        return new Envelope(subject: config('mail.prefix').' '.$subject);
    }

    public function content(): Content
    {
        $correction = $this->troop_correction->loadMissing(['trooper', 'event', 'event_trooper.event_shift']);

        return new Content(
            view: email_view('events.troop-correction-requested'),
            with: [
                'trooper_name' => $correction->trooper->display_name,
                'event_name' => $correction->event->name,
                'shift' => $correction->event_trooper->event_shift,
                'details' => $correction->details,
                'is_reminder' => $this->is_reminder,
                'days_left' => TroopCorrection::ESCALATE_AFTER_DAYS - TroopCorrection::REMIND_AFTER_DAYS,
                'review_url' => route('events.troop-correction', $correction),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
