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

    public function test_render_page_shows_every_page_and_matching_route_entries(): void
    {
        $this->subject->add('page.top', 'slot-test::greeting', ['greeting' => 'Everywhere'], order: 30);
        $this->subject->add('page.top:events.display', 'slot-test::greeting', ['greeting' => 'Event'], order: 10);
        $this->subject->add('page.top:events.*', 'slot-test::greeting', ['greeting' => 'Events'], order: 20);
        $this->subject->add('page.top:account.index', 'slot-test::greeting', ['greeting' => 'Account']);
        $this->subject->add('page.topping', 'slot-test::greeting', ['greeting' => 'Other slot']);

        $html = $this->subject->renderPage('page.top', [], 'events.display');

        $this->assertMatchesRegularExpression('/Event, nobody!.*Events, nobody!.*Everywhere, nobody!/s', $html);
        $this->assertStringNotContainsString('Account', $html);
        $this->assertStringNotContainsString('Other slot', $html);
    }

    public function test_render_page_without_a_route_shows_only_every_page_entries(): void
    {
        $this->subject->add('page.bottom', 'slot-test::greeting', ['greeting' => 'Everywhere']);
        $this->subject->add('page.bottom:*', 'slot-test::greeting', ['greeting' => 'Routed']);

        $html = $this->subject->renderPage('page.bottom', [], null);

        $this->assertStringContainsString('Everywhere', $html);
        $this->assertStringNotContainsString('Routed', $html);
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

    public function test_a_view_that_throws_inside_a_component_leaves_the_page_intact(): void
    {
        $this->subject->add('slot', 'slot-test::throws');
        $this->subject->add('slot', 'slot-test::greeting', ['greeting' => 'Still here']);

        $html = Blade::render(
            "<x-section-title>Before @pluginSlot('slot') After</x-section-title>",
            ['name' => 'Cody'],
        );

        $this->assertStringContainsString('<p>Still here, Cody!</p>', $html);
        $this->assertStringContainsString('After', $html);
    }

    public function test_plugin_slot_directive_renders_the_slot_with_view_variables(): void
    {
        $this->subject->add('slot', 'slot-test::greeting', ['greeting' => 'Hey']);

        $html = Blade::render("@pluginSlot('slot')", ['name' => 'Cody']);

        $this->assertStringContainsString('Hey, Cody!', $html);
    }

    public function test_render_many_returns_only_slots_with_output(): void
    {
        $this->subject->add('filled', 'slot-test::greeting', ['greeting' => 'Hi']);
        $this->subject->add('hidden', 'slot-test::greeting', ['greeting' => 'No'], fn (): bool => false);

        $result = $this->subject->renderMany(['filled', 'hidden', 'empty'], ['name' => 'Rex']);

        $this->assertSame(['filled'], array_keys($result));
        $this->assertStringContainsString('Hi, Rex!', $result['filled']);
    }
}
