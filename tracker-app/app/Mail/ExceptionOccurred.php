<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Trooper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Mailable for critical exception notifications sent to administrators.
 *
 * This email is sent to administrator troopers when an unhandled exception occurs
 * in the application. It provides detailed exception information to help admins:
 * - Identify the error type and message
 * - View the stack trace for debugging
 * - Understand the context in which the error occurred
 * - Take corrective action if needed
 *
 * Queued for asynchronous delivery to prevent blocking the error handling flow.
 * Implements ShouldQueue to ensure exceptions don't cascade into email sending failures.
 */
class ExceptionOccurred extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public function backoff(): array
    {
        return [30, 60];
    }

    /**
     * Create a new exception notification email instance.
     *
     * @param  Trooper  $trooper  The administrator trooper receiving the notification.
     * @param  Throwable  $exception  The exception that occurred in the application.
     * @param  array<string, mixed>  $context  Additional context data (e.g., request details, user info, environment).
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Throwable $exception,
        private readonly array $context = [])
    {
        //
    }

    /**
     * Get the message envelope with subject line.
     *
     * @return Envelope The email envelope configuration
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: config('mail.prefix').' Exception Occurred!'
        );
    }

    /**
     * Get the message content definition with trooper data.
     *
     * @return Content The email content configuration with view and trooper data
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.exception-occurred',
            with: [
                'trooper' => $this->trooper,
                'exception' => $this->exception,
                'context' => $this->context,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
