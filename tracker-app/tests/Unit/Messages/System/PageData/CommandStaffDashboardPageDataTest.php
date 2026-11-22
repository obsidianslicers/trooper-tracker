<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\System\PageData;

use App\Messages\System\PageData\CommandStaffDashboardPageData;
use App\Messages\System\Queries\GetSystemCheckProblems;
use App\Models\Trooper;
use App\Plugins\PluginSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class CommandStaffDashboardPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_moderator_gets_the_shared_tools_only(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $fake = $this->fakeMessages([GetSystemCheckProblems::class => collect()]);

        $result = $this->handle($moderator);
        $keys = array_column($result['cards'], 'key');

        $this->assertContains('trooper-approvals', $keys);
        $this->assertContains('request-permissions', $keys);
        $this->assertNotContains('permission-approvals', $keys);
        $this->assertNotContains('faq', $keys);
        $this->assertNotContains('system-check', $keys);
        $this->assertSame(0, $result['system_check_problems']);
        $this->assertSame(route('admin.troopers.index'), $result['cards'][3]['url']);
        $fake->assertNotCalled(GetSystemCheckProblems::class);
    }

    public function test_system_owner_gets_every_tool(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([GetSystemCheckProblems::class => collect()]);

        $keys = array_column($this->handle($owner)['cards'], 'key');

        foreach (['permission-approvals', 'merge-troopers', 'merge-costumes', 'faq', 'system-check', 'plugins'] as $key)
        {
            $this->assertContains($key, $keys);
        }
    }

    public function test_plugin_cards_are_rendered_with_the_counts(): void
    {
        $owner = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([GetSystemCheckProblems::class => collect()]);
        $slots = app(PluginSlots::class);
        view()->addNamespace('fixtures', base_path('tests/Fixtures/views'));
        $slots->add('admin.dashboard.cards', 'fixtures::dashboard-card');

        $result = $this->handle($owner);

        $this->assertStringContainsString('Plugin card 0', $result['plugin_cards']);
    }

    /**
     * @return array<string, mixed>
     */
    private function handle(Trooper $trooper): array
    {
        $this->actingAs($trooper);

        return (new CommandStaffDashboardPageData($trooper))
            ->handle(app(PluginSlots::class), Request::create('/admin'));
    }
}
