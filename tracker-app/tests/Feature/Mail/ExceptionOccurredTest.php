<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\ExceptionOccurred;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ExceptionOccurredTest extends TestCase
{
    use RefreshDatabase;

    public function test_envelope_contains_expected_subject(): void
    {
        config(['mail.prefix' => '[TEST]']);

        $trooper = Trooper::factory()->create();
        $mail = new ExceptionOccurred($trooper, new RuntimeException('Boom'));

        $this->assertSame('[TEST] Exception Occurred!', $mail->envelope()->subject);
    }

    public function test_content_contains_view_and_payload(): void
    {
        $trooper = Trooper::factory()->create();
        $exception = new RuntimeException('Failure');
        $context = ['path' => '/health'];

        $mail = new ExceptionOccurred($trooper, $exception, $context);
        $content = $mail->content();

        $this->assertSame('emails.exception-occurred', $content->view);
        $this->assertSame($trooper, $content->with['trooper']);
        $this->assertSame($exception, $content->with['exception']);
        $this->assertSame($context, $content->with['context']);
        $this->assertSame([], $mail->attachments());
    }
}
