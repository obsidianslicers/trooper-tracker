<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Notices;

use App\Enums\NoticeType;
use App\Http\Requests\Admin\Notices\UpdateRequest;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Organization $organization;

    private Notice $notice;

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

        $this->notice = Notice::factory()->create([
            Notice::ORGANIZATION_ID => $this->organization->id,
        ]);

        $this->actingAs($this->moderator);
    }

    /**
     * Helper method to set up a mocked route parameter
     */
    private function setupMockedRoute(UpdateRequest $request, ?Notice $notice): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('notice')
            ->andReturn($notice);
        $mock_route->shouldReceive('parameter')
            ->with('notice', \Mockery::any())
            ->andReturn($notice);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_moderator(): void
    {
        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, $this->notice);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_throws_exception_when_notice_not_found(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('Notice not found or unauthorized.');

        $subject = new UpdateRequest;
        $subject->setUserResolver(fn() => $this->moderator);
        $this->setupMockedRoute($subject, null);

        $subject->authorize();
    }

    public function test_rules_requires_title(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::TITLE, $rules);
        $this->assertContains('required', $rules[Notice::TITLE]);
    }

    public function test_rules_validates_title_max_length(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);

        $validator = Validator::make(
            [
                Notice::TITLE => str_repeat('a', 129), // max is 128
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::TITLE, $validator->errors()->toArray());
    }

    public function test_rules_requires_message(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::MESSAGE, $rules);
        $this->assertContains('required', $rules[Notice::MESSAGE]);
    }

    public function test_rules_requires_type(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::TYPE, $rules);
        $this->assertContains('required', $rules[Notice::TYPE]);
    }

    public function test_rules_validates_type_is_valid_enum(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => 'invalid-type',
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(7)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::TYPE, $validator->errors()->toArray());
    }

    public function test_rules_requires_starts_at(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::STARTS_AT, $rules);
        $this->assertContains('required', $rules[Notice::STARTS_AT]);
    }

    public function test_rules_requires_ends_at(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);
        $rules = $subject->rules();

        $this->assertArrayHasKey(Notice::ENDS_AT, $rules);
        $this->assertContains('required', $rules[Notice::ENDS_AT]);
    }

    public function test_rules_validates_ends_at_is_after_starts_at(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Test Notice',
                Notice::MESSAGE => 'Test message',
                Notice::TYPE => NoticeType::INFO->value,
                Notice::STARTS_AT => now()->addDays(7)->format('Y-m-d'),
                Notice::ENDS_AT => now()->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Notice::ENDS_AT, $validator->errors()->toArray());
    }

    public function test_rules_does_not_include_organization_id(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);
        $rules = $subject->rules();

        // Organization is immutable after creation
        $this->assertArrayNotHasKey(Notice::ORGANIZATION_ID, $rules);
    }

    public function test_rules_accepts_valid_update_data(): void
    {
        $subject = new UpdateRequest;
        $this->setupMockedRoute($subject, $this->notice);

        $validator = Validator::make(
            [
                Notice::TITLE => 'Updated Notice',
                Notice::MESSAGE => 'Updated message',
                Notice::TYPE => NoticeType::WARNING->value,
                Notice::STARTS_AT => now()->format('Y-m-d'),
                Notice::ENDS_AT => now()->addDays(14)->format('Y-m-d'),
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
