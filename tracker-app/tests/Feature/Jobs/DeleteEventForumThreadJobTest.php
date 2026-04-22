<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\DeleteEventForumThreadJob;
use App\Services\Forums\XenforoService;
use Mockery;
use Tests\TestCase;

class DeleteEventForumThreadJobTest extends TestCase
{
    public function test_handle_deletes_thread_when_xenforo_is_configured(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'test-key',
        ]);

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('delete_thread')
            ->once()
            ->with(321)
            ->andReturn([
                'status' => 204,
                'body' => null,
            ]);

        $subject = new DeleteEventForumThreadJob(123, 321);

        $subject->handle($xenforo);
    }

    public function test_handle_does_not_call_xenforo_when_not_configured(): void
    {
        config([
            'services.xenforo.base_url' => null,
            'services.xenforo.api_key' => null,
        ]);

        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('delete_thread');

        $subject = new DeleteEventForumThreadJob(123, 321);

        $subject->handle($xenforo);
    }
}
