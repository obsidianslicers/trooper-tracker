<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Casts;

use App\Models\Casts\SanitizeHtmlCast;
use App\Models\Event;
use Tests\TestCase;

class SanitizeHtmlCastTest extends TestCase
{
    public function test_set_strips_tags_and_decodes_entities(): void
    {
        $subject = new SanitizeHtmlCast();

        $result = $subject->set(
            new Event(),
            Event::NAME,
            '<b>Alert &amp; Ready</b> <script>alert(1)</script>',
            []
        );

        $this->assertSame('Alert & Ready alert(1)', $result);
    }

    public function test_null_values_are_preserved(): void
    {
        $subject = new SanitizeHtmlCast();

        $this->assertNull($subject->get(new Event(), Event::NAME, null, []));
        $this->assertNull($subject->set(new Event(), Event::NAME, null, []));
    }
}