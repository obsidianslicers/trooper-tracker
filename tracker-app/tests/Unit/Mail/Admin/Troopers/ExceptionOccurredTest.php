<?php

declare(strict_types=1);

namespace Tests\Unit\Mail\Admin\Troopers;

use App\Mail\ExceptionOccurred;
use App\Models\Trooper;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for ExceptionOccurred mailable.
 *
 * Verifies:
 * - Mailable implements ShouldQueue for asynchronous delivery
 * - Sets correct subject line
 * - Uses correct email template
 * - Passes trooper, exception, and context to view
 * - No attachments included
 */
class ExceptionOccurredTest extends TestCase
{
    use RefreshDatabase;

    public function test_mailable_implements_should_queue(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');

        // Act
        $subject = new ExceptionOccurred($trooper, $exception);

        // Assert
        $this->assertInstanceOf(ShouldQueue::class, $subject);
    }

    public function test_envelope_sets_correct_subject(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $envelope = $subject->envelope();

        // Assert
        $this->assertEquals('[Troop Tracker] Exception Occurred!', $envelope->subject);
    }

    public function test_content_uses_correct_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertEquals('emails.exception-occurred', $content->view);
    }

    public function test_content_passes_trooper_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create([
            Trooper::DISPLAY_NAME => 'Admin Trooper',
            Trooper::EMAIL => 'admin@501st.com',
        ]);
        $exception = new Exception('Test exception');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertArrayHasKey('trooper', $content->with);
        $this->assertSame($trooper, $content->with['trooper']);
    }

    public function test_content_passes_exception_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Critical error occurred');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertArrayHasKey('exception', $content->with);
        $this->assertSame($exception, $content->with['exception']);
    }

    public function test_content_passes_context_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $context = [
            'request_url' => '/events/123',
            'user_id' => 42,
            'environment' => 'production',
        ];
        $subject = new ExceptionOccurred($trooper, $exception, $context);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertArrayHasKey('context', $content->with);
        $this->assertEquals($context, $content->with['context']);
    }

    public function test_content_passes_empty_context_when_not_provided(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertArrayHasKey('context', $content->with);
        $this->assertEquals([], $content->with['context']);
    }

    public function test_attachments_returns_empty_array(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $attachments = $subject->attachments();

        // Assert
        $this->assertIsArray($attachments);
        $this->assertEmpty($attachments);
    }

    public function test_mailable_can_be_constructed_with_all_parameters(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Critical system error');
        $context = [
            'user_ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0',
            'route' => 'account.profile',
        ];

        // Act
        $subject = new ExceptionOccurred($trooper, $exception, $context);

        // Assert
        $this->assertInstanceOf(ExceptionOccurred::class, $subject);
    }

    public function test_content_includes_exception_message_in_view_data(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Database connection failed');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertEquals('Database connection failed', $content->with['exception']->getMessage());
    }

    public function test_content_includes_exception_stack_trace(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception with trace');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertIsString($content->with['exception']->getTraceAsString());
        $this->assertNotEmpty($content->with['exception']->getTraceAsString());
    }

    public function test_mailable_handles_different_exception_types(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new \RuntimeException('Runtime error occurred');
        $subject = new ExceptionOccurred($trooper, $exception);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertInstanceOf(\RuntimeException::class, $content->with['exception']);
        $this->assertEquals('Runtime error occurred', $content->with['exception']->getMessage());
    }

    public function test_context_can_contain_nested_arrays(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asAdministrator()->create();
        $exception = new Exception('Test exception');
        $context = [
            'request' => [
                'method' => 'POST',
                'url' => '/api/events',
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ],
            'user' => [
                'id' => 123,
                'name' => 'Test User',
            ],
        ];
        $subject = new ExceptionOccurred($trooper, $exception, $context);

        // Act
        $content = $subject->content();

        // Assert
        $this->assertEquals($context, $content->with['context']);
        $this->assertEquals('POST', $content->with['context']['request']['method']);
        $this->assertEquals(123, $content->with['context']['user']['id']);
    }
}
