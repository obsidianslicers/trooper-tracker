<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\Share;
use App\Facades\ShareFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareFacadeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'share.facebook.uri' => 'https://facebook.example/share?u=',
            'share.facebook.fa-icon' => 'fa-facebook-f',
        ]);
    }

    public function test_facade_delegates_to_share_service_and_returns_raw_link(): void
    {
        $this->instance(Share::class, new Share);

        $raw_link = ShareFacade::page('https://trooper.test/troops/5', 'Charity Troop')
            ->facebook()
            ->getRawLinks();

        $this->assertIsString($raw_link);
        $this->assertSame('https://facebook.example/share?u=https://trooper.test/troops/5', $raw_link);
    }

    public function test_facade_can_render_html_output_from_underlying_share_instance(): void
    {
        $this->instance(Share::class, new Share);

        $html = (string) ShareFacade::page('https://trooper.test/troops/6', 'Temple Run')->facebook();

        $this->assertStringContainsString('<div class="btn-group" role="group">', $html);
        $this->assertStringContainsString('fa-facebook-f', $html);
    }
}
