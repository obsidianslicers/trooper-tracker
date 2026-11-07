<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\MembershipRole;
use App\Jobs\SendTrooperRequestNotificationsJob;
use App\Messages\Troopers\Queries\GetAdministrators;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperRequest;
use App\Notifications\Admin\TrooperRequestSubmittedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

/**
 * @see SendTrooperRequestNotificationsJob
 */
class SendTrooperRequestNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_notifies_all_admins(): void
    {
        Notification::fake();

        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $admin_valid = Trooper::factory()->asAdministrator()->withEmail('admin@example.com')->create();
        $admin_invalid = Trooper::factory()->asAdministrator()->withInvalidEmail()->create();

        $messages = $this->fakeMessages([
            GetAdministrators::class => collect([$admin_valid, $admin_invalid]),
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::MODERATOR => collect([]),
            },
        ]);

        $subject = new SendTrooperRequestNotificationsJob($trooper_request);
        $subject->handle();

        $messages->assertCalled(GetAdministrators::class);
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertSentTo($admin_valid, TrooperRequestSubmittedNotification::class);
        Notification::assertSentTo($admin_invalid, TrooperRequestSubmittedNotification::class);
    }

    public function test_handle_notifies_moderators_with_authority_over_request(): void
    {
        Notification::fake();

        $root = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $child = Organization::factory()->asRegion()->withNodePath('100:200:')->withParent($root)->create();

        $trooper = Trooper::factory()->asMember()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($child)
            ->forPrimaryOrganization($root)
            ->create();

        $moderator_in_tree = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        TrooperAssignment::factory()->forTrooper($moderator_in_tree)->forOrganization($root)->asModerator()->create();

        $moderator_outside_tree = Trooper::factory()->asModerator()->withEmail('other-mod@example.com')->create();
        $other_org = Organization::factory()->asOrganization()->withNodePath('999:')->create();
        TrooperAssignment::factory()->forTrooper($moderator_outside_tree)->forOrganization($other_org)->asModerator()->create();

        $messages = $this->fakeMessages([
            GetAdministrators::class => collect([]),
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::MODERATOR => collect([$moderator_in_tree, $moderator_outside_tree]),
            },
        ]);

        $subject = new SendTrooperRequestNotificationsJob($trooper_request);
        $subject->handle();

        $messages->assertCalled(GetAdministrators::class);
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertSentTo($moderator_in_tree, TrooperRequestSubmittedNotification::class);
        Notification::assertNotSentTo($moderator_outside_tree, TrooperRequestSubmittedNotification::class);
    }
}
