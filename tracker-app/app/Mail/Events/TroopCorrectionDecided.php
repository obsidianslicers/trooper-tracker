<?php

declare(strict_types=1);

namespace App\Mail\Events;

use App\Enums\TroopCorrectionStatus;
use App\Mail\HasRetryPolicy;
use App\Models\TroopCorrection;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells a trooper whether their request for troop credit was approved or denied.
 */
class TroopCorrectionDecided extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    public function __construct(private readonly TroopCorrection $troop_correction) {}

    public function envelope(): Envelope
    {
        $subject = $this->isApproved()
            ? __('emails.Troop Credit Approved')
            : __('emails.Troop Credit Denied');

        return new Envelope(subject: config('mail.prefix').' '.$subject);
    }

    public function content(): Content
    {
        $event = $this->troop_correction->event;

        return new Content(
            view: email_view('events.troop-correction-decided'),
            with: [
                'event_name' => $event->name,
                'approved' => $this->isApproved(),
                'event_url' => route('events.display', $event),
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

    private function isApproved(): bool
    {
        return $this->troop_correction->status === TroopCorrectionStatus::APPROVED;
    }
}
