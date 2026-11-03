<?php

declare(strict_types=1);

namespace Tests\Unit\Plugins;

use App\Plugins\PluginSlots;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PluginSlotsTest extends TestCase
{
    private PluginSlots $subject;

    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('slot-test', base_path('tests/Fixtures/plugins/hello/resources/views'));

        $this->subject = new PluginSlots;
        $this->app->instance(PluginSlots::class, $this->subject);
    }

    public function test_render_returns_an_empty_string_for_an_empty_slot(): void
    {
        $this->assertFalse($this->subject->has('empty'));
        $this->assertSame('', $this->subject->render('empty'));
    }

    public function test_render_merges_view_context_with_entry_data_in_order(): void
    {
        $this->subject->add('slot', 'slot-test::greeting', ['greeting' => 'Second'], order: 20);
        $this->subject->add('slot', 'slot-test::greeting', fn (array $context): array => [
            'greeting' => 'First '.$context['name'],
        ], order: 10);

        $html = $this->subject->render('slot', ['name' => 'Rex', '__env' => 'hidden']);

        $this->assertTrue($this->subject->has('slot'));
        $this->assertMatchesRegularExpression('/First Rex, Rex!.*Second, Rex!/s', $html);
    }

    public function test_render_skips_entries_whose_condition_fails(): void
    {
        $this->subject->add('slot', 'slot-test::greeting', ['greeting' => 'Hidden'], fn (): bool => false);

        $this->assertSame('', $this->subject->render('slot'));
    }

    public function test_render_swallows_a_broken_view(): void
    {
        $this->subject->add('slot', 'slot-test::does-not-exist');
        $this->subject->add('slot', 'slot-test::greeting', ['greeting' => 'Still here']);

        $this->assertStringContainsString('Still here, nobody!', $this->subject->render('slot'));
    }

    public function test_plugin_slot_directive_renders_the_slot_with_view_variables(): void
    {
        $this->subject->add('slot', 'slot-test::greeting', ['greeting' => 'Hey']);

        $html = Blade::render("@pluginSlot('slot')", ['name' => 'Cody']);

        $this->assertStringContainsString('Hey, Cody!', $html);
    }
}
