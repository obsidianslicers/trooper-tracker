<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Enums\MembershipRole;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Messages\Troopers\Queries\GetAdministrators;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Notifications\Admin\TrooperRegisteredNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class SendTrooperRegisteredNotificationsJobTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_notifies_all_admins(): void
    {
        Notification::fake();

        $registered_trooper = Trooper::factory()->asMember()->create();

        $admin_valid = Trooper::factory()->asSystemOwner()->withEmail('admin@example.com')->create();
        $admin_invalid = Trooper::factory()->asSystemOwner()->withInvalidEmail()->create();

        $messages = $this->fakeMessages([
            GetAdministrators::class => collect([$admin_valid, $admin_invalid]),
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::MODERATOR => collect([]),
            },
        ]);

        $subject = new SendTrooperRegisteredNotificationsJob($registered_trooper);
        $subject->handle();

        $messages->assertCalled(GetAdministrators::class);
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertSentTo($admin_valid, TrooperRegisteredNotification::class);
        Notification::assertSentTo($admin_invalid, TrooperRegisteredNotification::class);
    }

    public function test_handle_notifies_moderators_with_authority_over_registered_trooper(): void
    {
        Notification::fake();

        $registered_trooper = Trooper::factory()->asMember()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();

        TrooperAssignment::factory()
            ->forTrooper($registered_trooper)
            ->forOrganization($descendant)
            ->asMember()
            ->create();

        $moderator_in_tree = Trooper::factory()->asModerator()->withEmail('mod@example.com')->create();
        TrooperAssignment::factory()
            ->forTrooper($moderator_in_tree)
            ->forOrganization($root)
            ->asModerator()
            ->create();

        $moderator_outside_tree = Trooper::factory()->asModerator()->withEmail('other@example.com')->create();

        $messages = $this->fakeMessages([
            GetAdministrators::class => collect([]),
            GetTroopersByRole::class => fn (array $params): mixed => match (true)
            {
                $params['membership_role'] === MembershipRole::MODERATOR => collect([$moderator_in_tree, $moderator_outside_tree]),
            },
        ]);

        $subject = new SendTrooperRegisteredNotificationsJob($registered_trooper);
        $subject->handle();

        $messages->assertCalled(GetAdministrators::class);
        $messages->assertCalled(
            GetTroopersByRole::class,
            fn (array $params): bool => $params['membership_role'] === MembershipRole::MODERATOR
        );

        Notification::assertSentTo($moderator_in_tree, TrooperRegisteredNotification::class);
        Notification::assertNotSentTo($moderator_outside_tree, TrooperRegisteredNotification::class);
    }
}
