<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Faq;
use Tests\TestCase;

class FaqTest extends TestCase
{
    public function test_embed_url_returns_null_when_no_video_url(): void
    {
        $faq = new Faq;
        $faq->video_url = null;

        $this->assertNull($faq->embedUrl());
    }

    public function test_embed_url_converts_youtube_watch_url(): void
    {
        $faq = new Faq;
        $faq->video_url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $faq->embedUrl());
    }

    public function test_embed_url_converts_youtu_be_short_url(): void
    {
        $faq = new Faq;
        $faq->video_url = 'https://youtu.be/dQw4w9WgXcQ';

        $this->assertSame('https://www.youtube.com/embed/dQw4w9WgXcQ', $faq->embedUrl());
    }

    public function test_embed_url_returns_original_url_for_non_youtube_links(): void
    {
        $faq = new Faq;
        $faq->video_url = 'https://vimeo.com/123456789';

        $this->assertSame('https://vimeo.com/123456789', $faq->embedUrl());
    }
}
