<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\Share;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ShareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'share.facebook.uri' => 'https://facebook.example/share?u=',
            'share.facebook.fa-icon' => 'fa-facebook-f',
            'share.twitter.uri' => 'https://twitter.example/share',
            'share.twitter.fa-icon' => 'fa-x-twitter',
            'share.reddit.uri' => 'https://reddit.example/submit',
            'share.reddit.fa-icon' => 'fa-reddit',
            'share.telegram.uri' => 'https://telegram.example/share/url',
            'share.telegram.fa-icon' => 'fa-telegram',
            'share.whatsapp.uri' => 'https://wa.example/send?text=',
            'share.whatsapp.fa-icon' => 'fa-whatsapp',
            'share.linkedin.uri' => 'https://linkedin.example/shareArticle',
            'share.linkedin.extra.mini' => 'true',
            'share.linkedin.fa-icon' => 'fa-linkedin',
            'share.pinterest.uri' => 'https://pinterest.example/pin/create/button/?url=',
            'share.pinterest.fa-icon' => 'fa-pinterest',
        ]);
    }

    public function test_each_provider_generates_expected_raw_links(): void
    {
        $subject = new Share;

        $raw_links = $subject
            ->page('https://trooper.test/troops/1', 'Founders Day Troop')
            ->facebook()
            ->twitter()
            ->reddit()
            ->telegram()
            ->whatsapp()
            ->linkedin()
            ->pinterest()
            ->getRawLinks();

        $this->assertIsArray($raw_links);
        $this->assertSame(
            'https://facebook.example/share?u=https://trooper.test/troops/1',
            $raw_links['facebook']
        );
        $this->assertSame(
            'https://twitter.example/share?text=Founders+Day+Troop&url=https://trooper.test/troops/1',
            $raw_links['twitter']
        );
        $this->assertSame(
            'https://reddit.example/submit?title=Founders+Day+Troop&url=https://trooper.test/troops/1',
            $raw_links['reddit']
        );
        $this->assertSame(
            'https://telegram.example/share/url?url=https://trooper.test/troops/1&text=Founders+Day+Troop',
            $raw_links['telegram']
        );
        $this->assertSame(
            'https://wa.example/send?text=https://trooper.test/troops/1',
            $raw_links['whatsapp']
        );
        $this->assertSame(
            'https://linkedin.example/shareArticle?mini=true&url=https://trooper.test/troops/1&title=Founders+Day+Troop',
            $raw_links['linkedin']
        );
        $this->assertSame(
            'https://pinterest.example/pin/create/button/?url=https://trooper.test/troops/1',
            $raw_links['pinterest']
        );
    }

    public function test_get_raw_links_returns_single_string_when_one_provider_exists(): void
    {
        $subject = new Share;

        $raw_link = $subject->page('https://trooper.test/troops/2', 'Night Patrol')->facebook()->getRawLinks();

        $this->assertIsString($raw_link);
        $this->assertSame('https://facebook.example/share?u=https://trooper.test/troops/2', $raw_link);
    }

    public function test_current_page_uses_request_uri_and_can_generate_provider_link(): void
    {
        $request = Request::create('https://trooper.test/shares/event/42?invite=abc', 'GET');
        $this->app->instance('request', $request);

        $subject = new Share;
        $raw_link = $subject->currentPage('Event Share')->facebook()->getRawLinks();

        $this->assertIsString($raw_link);
        $this->assertSame(
            'https://facebook.example/share?u=https://trooper.test/shares/event/42?invite=abc',
            $raw_link
        );
    }

    public function test_to_string_outputs_button_group_with_provider_icon_markup(): void
    {
        $subject = new Share;

        $html = (string) $subject
            ->page('https://trooper.test/troops/3', 'Parade Run')
            ->facebook()
            ->twitter();

        $this->assertStringContainsString('<div class="btn-group" role="group">', $html);
        $this->assertStringContainsString('fa-facebook-f', $html);
        $this->assertStringContainsString('fa-x-twitter', $html);
        $this->assertStringContainsString('target="_blank"', $html);
        $this->assertStringContainsString('rel="noopener noreferrer"', $html);
    }
}
