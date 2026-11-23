<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Commands;

use App\Enums\MembershipRole;
use App\Jobs\SendTrooperRegisteredNotificationsJob;
use App\Mail\Auth\GuardianAwareness;
use App\Mail\Auth\TrooperRegistered;
use App\Messages\Auth\Commands\RegisterTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegisterTrooperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Queue::fake();
        Event::fake([Registered::class]);
    }

    public function test_handle_creates_trooper_and_lets_everyone_know(): void
    {
        $subject = $this->subject(MembershipRole::MEMBER);
        $result = $subject->handle();

        $this->assertSame('rex@example.com', $result->email);
        $this->assertTrue($result->exists);
        Event::assertDispatched(Registered::class, fn (Registered $event) => $event->user->is($result));
        Mail::assertQueued(TrooperRegistered::class, fn ($mail) => $mail->hasTo('rex@example.com'));
        Mail::assertNotQueued(GuardianAwareness::class);
        Queue::assertPushed(SendTrooperRegisteredNotificationsJob::class);
    }

    public function test_handle_requests_the_unit_a_member_picked(): void
    {
        [$organization, $region, $unit] = $this->organizationHierarchy();

        $subject = $this->subject(MembershipRole::MEMBER, [
            $organization->id => [
                'selected' => '1',
                'identifier' => 'TK-1',
                'region_id' => $region->id,
                'unit_id' => $unit->id,
            ],
        ]);
        $result = $subject->handle();

        $this->assertDatabaseHas(TrooperRequest::class, [
            TrooperRequest::TROOPER_ID => $result->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::IDENTIFIER => 'TK-1',
        ]);
    }

    public function test_handle_skips_organizations_not_selected(): void
    {
        [$organization] = $this->organizationHierarchy();

        $subject = $this->subject(MembershipRole::VISITOR, [
            $organization->id => ['selected' => false, 'identifier' => 'TK-2'],
        ]);
        $result = $subject->handle();

        $this->assertDatabaseMissing(TrooperRequest::class, [TrooperRequest::TROOPER_ID => $result->id]);
    }

    public function test_handle_requests_no_organization_for_a_handler(): void
    {
        [$organization] = $this->organizationHierarchy();

        $subject = $this->subject(MembershipRole::HANDLER, [
            $organization->id => ['selected' => '1'],
        ]);
        $result = $subject->handle();

        $this->assertDatabaseMissing(TrooperRequest::class, [TrooperRequest::TROOPER_ID => $result->id]);
    }

    public function test_handle_tells_the_guardian_of_a_minor(): void
    {
        $guardian = Trooper::factory()->asActive()->create();

        $subject = new RegisterTrooper(
            legal_name: 'Rex',
            display_name: 'Rex',
            email: 'rex@example.com',
            membership_role: MembershipRole::MEMBER,
            date_of_birth: now()->subYears(15)->toDateString(),
            guardian_email: $guardian->email,
        );
        $subject->handle();

        Mail::assertQueued(GuardianAwareness::class, fn ($mail) => $mail->hasTo($guardian->email));
    }

    private function subject(MembershipRole $role, array $organizations = []): RegisterTrooper
    {
        return new RegisterTrooper(
            legal_name: 'Rex',
            display_name: 'Rex',
            email: 'rex@example.com',
            membership_role: $role,
            password: 'secret',
            organizations: $organizations,
        );
    }

    private function organizationHierarchy(): array
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withNodePath('100:200:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();

        return [$organization, $region, $unit];
    }
}
