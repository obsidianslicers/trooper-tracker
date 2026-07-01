<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ForumBBCodeRenderer;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class ForumBBCodeRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_renders_xenforo_quote_with_attribution(): void
    {
        $html = ForumBBCodeRenderer::toHtml(
            '[QUOTE="WhiteWalker, post: 662360, member: 14475"]'."\n"
            .'This is the quoted text.'."\n"
            .'[/QUOTE]'
        );

        $this->assertStringContainsString('<blockquote class="border-start ps-3 my-2">', $html);
        $this->assertStringContainsString('<div class="small text-muted mb-1">WhiteWalker said:</div>', $html);
        $this->assertStringContainsString('This is the quoted text.', $html);
        $this->assertStringContainsString('</blockquote>', $html);
    }

    public function test_user_mention_links_to_tracker_profile_when_trooper_is_linked(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        OauthLogin::factory()->forTrooper($trooper)->forProvider('xenforo')->create([
            OauthLogin::PROVIDER_ID => '18076',
        ]);

        $html = ForumBBCodeRenderer::toHtml('[USER=18076]@DownedDak[/USER]');

        $expected_href = route('service-records.trooper', $trooper);
        $this->assertStringContainsString('href="'.$expected_href.'"', $html);
        $this->assertStringContainsString('@DownedDak', $html);
    }

    public function test_user_mention_falls_back_to_forum_profile_when_trooper_is_not_linked(): void
    {
        Config::set('services.xenforo.base_url', 'https://forum.example.com');

        $html = ForumBBCodeRenderer::toHtml('[USER=18076]@DownedDak[/USER]');

        $this->assertStringContainsString('href="https://forum.example.com/index.php?members/18076/"', $html);
        $this->assertStringContainsString('@DownedDak', $html);
    }

    public function test_user_mention_renders_plain_text_when_xenforo_not_configured(): void
    {
        Config::set('services.xenforo.base_url', '');

        $html = ForumBBCodeRenderer::toHtml('[USER=18076]@DownedDak[/USER]');

        $this->assertStringContainsString('@DownedDak', $html);
        $this->assertStringNotContainsString('<a ', $html);
    }

    public function test_user_mention_display_name_is_xss_safe(): void
    {
        Config::set('services.xenforo.base_url', 'https://forum.example.com');

        $html = ForumBBCodeRenderer::toHtml('[USER=1]<script>alert(1)</script>[/USER]');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_quote_attribution_is_escaped(): void
    {
        $html = ForumBBCodeRenderer::toHtml('[quote="<script>alert(1)</script>, post: 1"]Hello[/quote]');

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; said:', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}
