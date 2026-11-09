<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Messages\Account\PageData\IndexPageData;
use App\Models\Trooper;
use App\Plugins\PluginNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_gives_administrators_every_administrative_notification(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = new IndexPageData($trooper);

        $values = $this->administrativeValues($subject->handle());

        $this->assertEqualsCanonicalizing(
            array_column(AdministrativeNotifications::cases(), 'value'),
            $values
        );
    }

    public function test_handle_gives_moderators_administrative_notifications_without_admin_only(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();
        $subject = new IndexPageData($trooper);

        $values = $this->administrativeValues($subject->handle());

        $this->assertContains(AdministrativeNotifications::TROOPER_MILESTONES->value, $values);
        $this->assertContains(AdministrativeNotifications::TROOPER_REQUESTS->value, $values);
        $this->assertContains(AdministrativeNotifications::TROOPER_REGISTRATIONS->value, $values);
        $this->assertNotContains(AdministrativeNotifications::TROOPERS_MERGED->value, $values);
    }

    public function test_handle_gives_members_no_administrative_notifications(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $subject = new IndexPageData($trooper);

        $this->assertSame([], $this->administrativeValues($subject->handle()));
    }

    public function test_handle_fills_sparse_notification_preferences_with_defaults(): void
    {
        $key = AdministrativeNotifications::TROOPER_REQUESTS->value;
        $trooper = Trooper::factory()->asMember()->create([
            'notification_preferences' => [$key => ['mail' => false]],
        ]);
        $subject = new IndexPageData($trooper);

        $preferences = $subject->handle()['notifications']['notification_preferences'];

        $this->assertEquals(['mail' => false, 'fcm' => true, 'database' => true], $preferences[$key]);
        $this->assertCount(
            count(AdministrativeNotifications::cases()) + count(TrooperNotifications::cases()),
            $preferences
        );
    }

    public function test_handle_includes_plugin_notification_categories(): void
    {
        $plugins = $this->app->make(PluginNotifications::class);
        $plugins->add('forum_reply', 'Forum Reply');
        $plugins->add('forum_flag', 'Forum Flag', for_command_staff: true);
        $plugins->add('forum_audit', 'Forum Audit', administrator_only: true);

        $moderator = (new IndexPageData(Trooper::factory()->asModerator()->create()))->handle();
        $administrator = (new IndexPageData(Trooper::factory()->asSystemOwner()->create()))->handle();

        $trooper_values = array_column($moderator['notifications']['trooper_notification_enums'], 'value');
        $this->assertContains('forum_reply', $trooper_values);
        $this->assertNotContains('forum_flag', $trooper_values);
        $this->assertContains('forum_flag', $this->administrativeValues($moderator));
        $this->assertNotContains('forum_audit', $this->administrativeValues($moderator));
        $this->assertContains('forum_audit', $this->administrativeValues($administrator));
        $this->assertEquals(
            ['mail' => true, 'fcm' => true, 'database' => true],
            $moderator['notifications']['notification_preferences']['forum_reply'],
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, string>
     */
    private function administrativeValues(array $data): array
    {
        return array_column($data['notifications']['administrative_notification_enums'], 'value');
    }
}
