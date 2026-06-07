<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Webhooks;

use App\Http\Controllers\Webhooks\XenforoWebhookController;
use App\Jobs\SendEventForumPostNotificationsJob;
use App\Models\Event;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * @see XenforoWebhookController
 */
class XenforoWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private function configureSecret(string $secret = 'test-secret'): void
    {
        config(['services.xenforo.webhook_secret' => $secret]);
    }

    private function validPayload(int $thread_id = 100): array
    {
        return [
            'content_type' => 'post',
            'event'        => 'insert',
            'content_id'   => 999,
            'data'         => [
                'thread_id' => $thread_id,
                'post_id'   => 999,
                'username'  => 'TestUser',
                'user_id'   => 1,
                'message'   => 'Test forum reply',
            ],
        ];
    }

    public function test_invoke_returns_401_when_secret_header_is_missing(): void
    {
        $this->configureSecret();

        $response = $this->postJson('/webhooks/xenforo', $this->validPayload());

        $response->assertStatus(401);
    }

    public function test_invoke_returns_401_when_secret_does_not_match(): void
    {
        $this->configureSecret('correct-secret');

        $response = $this->postJson('/webhooks/xenforo', $this->validPayload(), [
            'xf-webhook-secret' => 'wrong-secret',
        ]);

        $response->assertStatus(401);
    }

    public function test_invoke_returns_401_when_webhook_secret_is_not_configured(): void
    {
        config(['services.xenforo.webhook_secret' => null]);

        $response = $this->postJson('/webhooks/xenforo', $this->validPayload(), [
            'xf-webhook-secret' => 'anything',
        ]);

        $response->assertStatus(401);
    }

    public function test_invoke_dispatches_job_for_valid_insert_on_tracked_event(): void
    {
        Queue::fake();
        $this->configureSecret();

        Event::factory()->withForumThreadId(100)->create();

        $response = $this->postJson('/webhooks/xenforo', $this->validPayload(100), [
            'xf-webhook-secret' => 'test-secret',
        ]);

        $response->assertStatus(204);
        Queue::assertPushed(SendEventForumPostNotificationsJob::class);
    }

    public function test_invoke_returns_204_without_dispatching_when_no_event_matches_thread_id(): void
    {
        Queue::fake();
        $this->configureSecret();

        $response = $this->postJson('/webhooks/xenforo', $this->validPayload(99999), [
            'xf-webhook-secret' => 'test-secret',
        ]);

        $response->assertStatus(204);
        Queue::assertNotPushed(SendEventForumPostNotificationsJob::class);
    }

    public function test_invoke_returns_204_without_dispatching_when_event_is_not_insert(): void
    {
        Queue::fake();
        $this->configureSecret();

        Event::factory()->withForumThreadId(100)->create();

        $payload = $this->validPayload(100);
        $payload['event'] = 'update';

        $response = $this->postJson('/webhooks/xenforo', $payload, [
            'xf-webhook-secret' => 'test-secret',
        ]);

        $response->assertStatus(204);
        Queue::assertNotPushed(SendEventForumPostNotificationsJob::class);
    }

    public function test_invoke_returns_204_without_dispatching_when_thread_id_missing(): void
    {
        Queue::fake();
        $this->configureSecret();

        $payload = [
            'content_type' => 'post',
            'event'        => 'insert',
            'content_id'   => 999,
            'data'         => ['post_id' => 999, 'message' => 'test'],
        ];

        $response = $this->postJson('/webhooks/xenforo', $payload, [
            'xf-webhook-secret' => 'test-secret',
        ]);

        $response->assertStatus(204);
        Queue::assertNotPushed(SendEventForumPostNotificationsJob::class);
    }
}
