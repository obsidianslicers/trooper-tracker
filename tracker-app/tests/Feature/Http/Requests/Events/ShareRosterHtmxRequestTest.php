<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Events;

use App\Http\Requests\Events\ShareRosterHtmxRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ShareRosterHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $this->organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->event = Event::factory()->create([
            Event::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->actingAs($this->moderator);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(ShareRosterHtmxRequest $request, ?Event $event): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('event')
            ->andReturn($event);
        $mock_route->shouldReceive('parameter')
            ->with('event', \Mockery::any())
            ->andReturn($event);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_regular_member(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new ShareRosterHtmxRequest;
        $subject->setUserResolver(fn() => $member);
        $this->setupMockedRoute($subject, $this->event);

        $this->assertFalse($subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');

        $subject = new ShareRosterHtmxRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_recipient_email(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertArrayHasKey('recipient_email', $rules);
        $this->assertContains('required', $rules['recipient_email']);
    }

    public function test_rules_validates_recipient_email_is_string(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $this->setupMockedRoute($subject, $this->event);
        $rules = $subject->rules();

        $this->assertContains('string', $rules['recipient_email']);
    }

    public function test_rules_validates_recipient_email_format(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'recipient_email' => 'not-an-email',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('recipient_email', $validator->errors()->toArray());
    }

    public function test_rules_accepts_valid_email(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'recipient_email' => 'coordinator@example.com',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_fails_when_recipient_email_is_missing(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('recipient_email', $validator->errors()->toArray());
    }

    public function test_rules_accepts_email_with_subdomain(): void
    {
        $subject = new ShareRosterHtmxRequest;
        $this->setupMockedRoute($subject, $this->event);

        $validator = Validator::make(
            [
                'recipient_email' => 'coordinator@mail.example.com',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
