<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Http\Controllers\Events\ForumReplyController;
use App\Jobs\SendForumPostCommandStaffNotificationsJob;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @see ForumReplyController
 */
class ForumReplyControllerTest extends TestCase
{
    use RefreshDatabase;

    private function configureXenforo(): void
    {
        config([
            'services.xenforo.base_url' => 'https://forums.example.com',
            'services.xenforo.api_key' => 'test-api-key',
        ]);
    }

    private function mockXenforoSuccess(Trooper $trooper, int $thread_id): void
    {
        $this->mock(XenforoService::class, function (MockInterface $mock) use ($trooper): void {
            $mock->shouldReceive('resolve_user_id_for_trooper')
                ->once()
                ->with($trooper->id)
                ->andReturn(99);

            $mock->shouldReceive('create_post')
                ->once()
                ->andReturn(['status' => 200]);

            $mock->shouldReceive('get_smilies')
                ->once()
                ->andReturn([]);

            $mock->shouldReceive('get_thread_posts')
                ->once()
                ->andReturn([]);
        });
    }

    public function test_invoke_dispatches_job_when_notify_command_staff_is_checked(): void
    {
        Queue::fake();
        $this->configureXenforo();

        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->withOrganization($organization)->withForumThreadId(42)->create();

        $this->mockXenforoSuccess($trooper, 42);

        $this->actingAs($trooper)->post(route('events.forum-reply-htmx', compact('event')), [
            'message' => 'Help needed from command staff!',
            'notify_command_staff' => 1,
        ]);

        Queue::assertPushed(SendForumPostCommandStaffNotificationsJob::class);
    }

    public function test_invoke_does_not_dispatch_job_when_checkbox_is_absent(): void
    {
        Queue::fake();
        $this->configureXenforo();

        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->withOrganization($organization)->withForumThreadId(42)->create();

        $this->mockXenforoSuccess($trooper, 42);

        $this->actingAs($trooper)->post(route('events.forum-reply-htmx', compact('event')), [
            'message' => 'Just a regular reply.',
        ]);

        Queue::assertNotPushed(SendForumPostCommandStaffNotificationsJob::class);
    }

    public function test_invoke_does_not_dispatch_job_when_xenforo_post_fails(): void
    {
        Queue::fake();
        $this->configureXenforo();

        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->withOrganization($organization)->withForumThreadId(42)->create();

        $this->mock(XenforoService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('resolve_user_id_for_trooper')->once()->andReturn(99);
            $mock->shouldReceive('create_post')->once()->andReturn(['status' => 500]);
            $mock->shouldReceive('get_smilies')->once()->andReturn([]);
            $mock->shouldReceive('get_thread_posts')->once()->andReturn([]);
        });

        $this->actingAs($trooper)->post(route('events.forum-reply-htmx', compact('event')), [
            'message' => 'Help needed!',
            'notify_command_staff' => 1,
        ]);

        Queue::assertNotPushed(SendForumPostCommandStaffNotificationsJob::class);
    }

    public function test_invoke_aborts_when_xenforo_is_not_configured(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->withForumThreadId(42)->create();

        $response = $this->actingAs($trooper)->post(route('events.forum-reply-htmx', compact('event')), [
            'message' => 'Hello!',
        ]);

        $response->assertStatus(422);
    }

    public function test_invoke_aborts_when_event_has_no_thread_id(): void
    {
        $this->configureXenforo();

        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create([Event::THREAD_ID => null]);

        $response = $this->actingAs($trooper)->post(route('events.forum-reply-htmx', compact('event')), [
            'message' => 'Hello!',
        ]);

        $response->assertStatus(422);
    }

    public function test_invoke_requires_authentication(): void
    {
        $event = Event::factory()->create();

        $response = $this->post(route('events.forum-reply-htmx', compact('event')), [
            'message' => 'Hello!',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
