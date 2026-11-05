<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Plugins\PluginSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PageSlotsTest extends TestCase
{
    use RefreshDatabase;

    private PluginSlots $slots;

    protected function setUp(): void
    {
        parent::setUp();

        View::addNamespace('slot-test', base_path('tests/Fixtures/plugins/hello/resources/views'));
        $this->slots = $this->app->make(PluginSlots::class);
    }

    public function test_blade_pages_show_every_page_slot(): void
    {
        foreach (PluginSlots::PAGE_SLOTS as $slot)
        {
            $this->slots->add($slot, 'slot-test::greeting', ['greeting' => "In {$slot}"]);
        }
        $this->slots->add('page.top:faq', 'slot-test::greeting', ['greeting' => 'Only on faq']);
        $this->slots->add('page.top:events.*', 'slot-test::greeting', ['greeting' => 'Only on events']);

        $response = $this->get(route('faq'))->assertOk();

        foreach (PluginSlots::PAGE_SLOTS as $slot)
        {
            $response->assertSee("In {$slot}");
        }
        $response->assertSee('Only on faq')->assertDontSee('Only on events');
        $response->assertSeeInOrder(['In page.head', '</head>', 'In page.top', 'In page.bottom', 'In page.scripts'], false);
    }

    public function test_svelte_pages_get_the_top_and_bottom_slots_for_their_route(): void
    {
        $this->slots->add('page.top', 'slot-test::greeting', ['greeting' => 'Top']);
        $this->slots->add('page.bottom:auth.login', 'slot-test::greeting', ['greeting' => 'Login bottom']);
        $this->slots->add('page.bottom:auth.signup', 'slot-test::greeting', ['greeting' => 'Signup bottom']);

        $slots = $this->get(route('auth.login'))->assertOk()->inertiaProps('plugin_slots');

        $this->assertStringContainsString('Top, nobody!', $slots['page.top']);
        $this->assertStringContainsString('Login bottom', $slots['page.bottom']);
        $this->assertStringNotContainsString('Signup bottom', $slots['page.bottom']);
    }
}
