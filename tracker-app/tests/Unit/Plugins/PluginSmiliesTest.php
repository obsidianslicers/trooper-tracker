<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Plugins\PluginSmilies;
use PHPUnit\Framework\TestCase;

class PluginSmiliesTest extends TestCase
{
    public function test_all_is_empty_without_a_source(): void
    {
        $subject = new PluginSmilies;

        $this->assertSame([], $subject->all());
    }

    public function test_all_keeps_smilies_with_a_code_and_picture(): void
    {
        $subject = new PluginSmilies;
        $subject->set(fn (): array => [
            ['title' => 'Smile', 'code' => ':)', 'image_url' => 'https://forum.test/smile.png'],
            ['title' => 'No code', 'code' => '', 'image_url' => 'https://forum.test/x.png'],
            ['title' => 'No picture', 'code' => ':(', 'image_url' => ''],
        ]);

        $this->assertSame(
            [['title' => 'Smile', 'code' => ':)', 'image_url' => 'https://forum.test/smile.png']],
            $subject->all()
        );
    }
}
