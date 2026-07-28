<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Troopers;

use App\Http\Requests\Admin\Troopers\MergeTroopersRequest;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MergeTroopersRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $administrator;

    private Trooper $target_trooper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->administrator = Trooper::factory()->asAdministrator()->asActive()->create();
        $this->target_trooper = Trooper::factory()->asMember()->asActive()->create();
        $this->actingAs($this->administrator);
    }

    private function setupMockedRoute(MergeTroopersRequest $request, ?Trooper $trooper): void
    {
        $mock_route = \Mockery::mock();
        $mock_route->shouldReceive('parameter')
            ->with('trooper')
            ->andReturn($trooper);
        $mock_route->shouldReceive('parameter')
            ->with('trooper', \Mockery::any())
            ->andReturn($trooper);
        $request->setRouteResolver(fn() => $mock_route);
    }

    public function test_authorize_returns_true_for_administrator(): void
    {
        $subject = new MergeTroopersRequest;
        $subject->setUserResolver(fn() => $this->administrator);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_administrator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $subject = new MergeTroopersRequest;
        $subject->setUserResolver(fn() => $moderator);
        $this->setupMockedRoute($subject, $this->target_trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_requires_source_and_target_trooper_ids(): void
    {
        $subject = new MergeTroopersRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey('source_trooper_id', $rules);
        $this->assertArrayHasKey('target_trooper_id', $rules);
        $this->assertContains('required', $rules['source_trooper_id']);
        $this->assertContains('required', $rules['target_trooper_id']);
    }

    public function test_rules_accept_valid_trooper_ids(): void
    {
        $source_trooper = Trooper::factory()->asActive()->create();
        $target_trooper = Trooper::factory()->asActive()->create();

        $subject = new MergeTroopersRequest;

        $validator = Validator::make(
            [
                'source_trooper_id' => $source_trooper->id,
                'target_trooper_id' => $target_trooper->id,
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_reject_missing_source_trooper_id(): void
    {
        $subject = new MergeTroopersRequest;

        $validator = Validator::make(
            [
                'target_trooper_id' => $this->target_trooper->id,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source_trooper_id', $validator->errors()->toArray());
    }

    public function test_rules_reject_missing_target_trooper_id(): void
    {
        $subject = new MergeTroopersRequest;

        $validator = Validator::make(
            [
                'source_trooper_id' => $this->administrator->id,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('target_trooper_id', $validator->errors()->toArray());
    }

    public function test_rules_reject_invalid_trooper_ids(): void
    {
        $subject = new MergeTroopersRequest;

        $validator = Validator::make(
            [
                'source_trooper_id' => 999999,
                'target_trooper_id' => 888888,
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('source_trooper_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('target_trooper_id', $validator->errors()->toArray());
    }

    public function test_messages_provide_custom_errors(): void
    {
        $subject = new MergeTroopersRequest;
        $messages = $subject->messages();

        $this->assertArrayHasKey('source_trooper_id.required', $messages);
        $this->assertArrayHasKey('source_trooper_id.exists', $messages);
        $this->assertArrayHasKey('target_trooper_id.required', $messages);
        $this->assertArrayHasKey('target_trooper_id.exists', $messages);
        $this->assertSame('Source trooper is required.', $messages['source_trooper_id.required']);
        $this->assertSame(
            'The selected source trooper does not exist (or is not active).',
            $messages['source_trooper_id.exists']
        );
        $this->assertSame('Target trooper is required.', $messages['target_trooper_id.required']);
        $this->assertSame(
            'The selected target trooper does not exist (or is not active).',
            $messages['target_trooper_id.exists']
        );
    }
}