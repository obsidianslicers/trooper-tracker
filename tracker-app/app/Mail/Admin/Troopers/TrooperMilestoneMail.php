<?php

declare(strict_types=1);

namespace App\Mail\Admin\Troopers;

use App\Mail\HasRetryPolicy;
use App\Models\TrooperAchievement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class TrooperMilestoneMail extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /** @param Collection<int, TrooperAchievement> $achievements */
    public function __construct(public readonly Collection $achievements) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Daily Trooper Milestones',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.troopers.trooper-milestone',
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
