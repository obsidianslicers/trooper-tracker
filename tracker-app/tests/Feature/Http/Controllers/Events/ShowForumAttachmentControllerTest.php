<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Http\Controllers\Events\ShowForumAttachmentController;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * @see ShowForumAttachmentController
 */
class ShowForumAttachmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_serves_image_inline(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->mockAttachment(7398, ['content' => 'png-bytes', 'content_type' => 'image/png', 'filename' => 'photo.png']);

        $response = $this->actingAs($trooper)->get($this->signedUrl(7398));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'image/png');
        $response->assertHeader('Content-Disposition', 'inline; filename=photo.png');
        $this->assertSame('png-bytes', $response->getContent());
    }

    public function test_invoke_downloads_non_image_attachments(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->mockAttachment(12, ['content' => '<svg></svg>', 'content_type' => 'image/svg+xml', 'filename' => 'flyer.svg']);

        $response = $this->actingAs($trooper)->get($this->signedUrl(12));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/octet-stream');
        $response->assertHeader('Content-Disposition', 'attachment; filename=flyer.svg');
        $response->assertHeader('Content-Security-Policy', 'sandbox');
    }

    public function test_invoke_returns_not_found_when_attachment_is_missing(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->mockAttachment(5, null);

        $this->actingAs($trooper)->get($this->signedUrl(5))->assertNotFound();
    }

    public function test_invoke_rejects_unsigned_urls(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $this->mock(XenforoService::class, function (MockInterface $mock): void
        {
            $mock->shouldNotReceive('get_attachment_data');
        });

        $this->actingAs($trooper)
            ->get(route('events.forum-attachment', ['attachment_id' => 7398]))
            ->assertRedirect();
    }

    /**
     * @param  array{content:string,content_type:string,filename:?string}|null  $result
     */
    private function mockAttachment(int $attachment_id, ?array $result): void
    {
        $this->mock(XenforoService::class, function (MockInterface $mock) use ($attachment_id, $result): void
        {
            $mock->shouldReceive('get_attachment_data')
                ->once()
                ->with($attachment_id)
                ->andReturn($result);
        });
    }

    private function signedUrl(int $attachment_id): string
    {
        return URL::signedRoute('events.forum-attachment', ['attachment_id' => $attachment_id]);
    }
}
