<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Models\Organization;
use App\Plugins\PluginForms;
use PHPUnit\Framework\TestCase;

class PluginFormsTest extends TestCase
{
    public function test_rules_merge_every_extension_for_the_form(): void
    {
        $subject = new PluginForms;
        $subject->add('form', ['a' => ['required']]);
        $subject->add('form', ['b' => ['nullable']]);
        $subject->add('other', ['c' => ['nullable']]);

        $this->assertSame(['a' => ['required'], 'b' => ['nullable']], $subject->rules('form'));
        $this->assertSame([], $subject->rules('missing'));
    }

    public function test_saved_calls_each_callback_with_the_model_and_input(): void
    {
        $subject = new PluginForms;
        $calls = [];
        $subject->add('form', saved: function (
            Organization $model,
            array $validated,
        ) use (&$calls): void
        {
            $calls[] = $validated['a'];
        });
        $subject->add('form', ['b' => ['nullable']]);

        $subject->saved('form', new Organization, ['a' => 'value']);

        $this->assertSame(['value'], $calls);
    }
}
