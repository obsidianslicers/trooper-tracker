<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ForumBBCodeRenderer;
use Tests\TestCase;

class ForumBBCodeRendererTest extends TestCase
{
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

    public function test_quote_attribution_is_escaped(): void
    {
        $html = ForumBBCodeRenderer::toHtml('[quote="<script>alert(1)</script>, post: 1"]Hello[/quote]');

        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt; said:', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}
