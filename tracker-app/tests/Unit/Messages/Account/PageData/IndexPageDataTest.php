<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Enums\AdministrativeNotifications;
use App\Messages\Account\PageData\IndexPageData;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndexPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_gives_administrators_every_administrative_notification(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
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
        $this->assertContains(AdministrativeNotifications::FORUM_POST_COMMAND_STAFF->value, $values);
        $this->assertNotContains(AdministrativeNotifications::TROOPERS_MERGED->value, $values);
    }

    public function test_handle_gives_members_no_administrative_notifications(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $subject = new IndexPageData($trooper);

        $this->assertSame([], $this->administrativeValues($subject->handle()));
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
