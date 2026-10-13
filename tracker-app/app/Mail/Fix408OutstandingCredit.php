<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Reports EventTrooper records the Fix408 false-membership correction could not re-resolve.
 *
 * Sent to administrator troopers after Fix408 finishes running, listing ATTENDED records that
 * lost credit to a false club membership and couldn't be re-resolved via live eligibility or the
 * legacy signup fallback. These require manual review.
 *
 * Queued for asynchronous delivery so the seeder run isn't blocked on mail delivery.
 */
class Fix408OutstandingCredit extends Mailable implements ShouldQueue
{
    use HasRetryPolicy;
    use Queueable, SerializesModels;

    /**
     * @param  Trooper  $trooper  The administrator trooper receiving the report.
     * @param  array<int, array{
     *     event_trooper_id: int,
     *     trooper_name: string,
     *     event_name: string,
     *     event_id: ?int,
     *     costume_name: ?string,
     *     reason: string,
     * }>  $outstanding_rows
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $outstanding_rows)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Fix408: Outstanding Credit Records',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.fix408-outstanding-credit',
            with: [
                'trooper' => $this->trooper,
                'outstanding_rows' => $this->outstanding_rows,
            ],
        );
    }
}
