<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Requests\Events;

use App\Http\Requests\Events\ShareRosterHtmxRequest;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ShareRosterHtmxRequestTest extends TestCase
{
    use RefreshDatabase;

    private ShareRosterHtmxRequest $subject;
    private Trooper $user;
    private Organization $organization;
    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ShareRosterHtmxRequest();
        $this->organization = Organization::factory()->create();
        $this->user = Trooper::factory()
            ->withAssignment($this->organization, moderator: true)
            ->create();
        $this->event = Event::factory()->withOrganization($this->organization)->create();

        $this->subject->setUserResolver(fn() => $this->user);
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute(['event' => $this->event]);
        });
    }

    private function getMockRoute(array $parameters = []): object
    {
        return new class ($parameters)
        {
            public function __construct(private array $parameters)
            {
            }

            public function parameter(string $key, $default = null)
            {
                return $this->parameters[$key] ?? $default;
            }
        };
    }

    public function test_authorize_returns_true_for_event_moderator(): void
    {
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $this->subject->setUserResolver(fn() => $admin);

        // Act & Assert
        $this->assertTrue($this->subject->authorize());
    }

    public function test_authorize_returns_false_for_non_moderator(): void
    {
        // Arrange
        $regular_user = Trooper::factory()->asActive()->create();
        $this->subject->setUserResolver(fn() => $regular_user);

        // Act & Assert
        $this->assertFalse($this->subject->authorize());
    }

    public function test_authorize_throws_exception_when_event_not_in_route(): void
    {
        // Arrange
        $this->subject->setRouteResolver(function ()
        {
            return $this->getMockRoute([]);
        });

        // Act & Assert
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Event not found or unauthorized.');
        $this->subject->authorize();
    }

    public function test_validation_passes_with_valid_coordinator_email(): void
    {
        // Arrange
        $good_data = [
            'recipient_email' => 'coordinator@example.com',
        ];

        // Act
        $this->subject->merge($good_data);
        $validator = Validator::make($good_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_when_coordinator_email_missing(): void
    {
        // Arrange
        $bad_data = [];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('recipient_email'));
    }

    public function test_validation_fails_with_invalid_email_format(): void
    {
        // Arrange
        $bad_data = [
            'recipient_email' => 'not-an-email',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('recipient_email'));
    }

    public function test_validation_fails_with_empty_string_coordinator_email(): void
    {
        // Arrange
        $bad_data = [
            'recipient_email' => '',
        ];

        // Act
        $this->subject->merge($bad_data);
        $validator = Validator::make($bad_data, $this->subject->rules());

        // Assert
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('recipient_email'));
    }

    public function test_validation_passes_with_various_valid_email_formats(): void
    {
        // Arrange
        $valid_emails = [
            'simple@example.com',
            'user+tag@example.co.uk',
            'firstname.lastname@example.org',
            'test.email@sub.example.com',
        ];

        // Act & Assert
        foreach ($valid_emails as $email)
        {
            $data = ['recipient_email' => $email];
            $this->subject->merge($data);
            $validator = Validator::make($data, $this->subject->rules());
            $this->assertTrue($validator->passes(), "Email $email should be valid");
        }
    }
}
