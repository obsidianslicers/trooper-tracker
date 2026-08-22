<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Account;

use App\Http\Requests\Account\RequestDeletionRequest;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestDeletionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_returns_true_for_own_account_without_pending_request(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $subject = new RequestDeletionRequest;
        $subject->setUserResolver(fn() => $trooper);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_when_a_previous_deletion_request_exists(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::DELETION_REQUESTED_AT => now(),
        ]);
        $subject = new RequestDeletionRequest;
        $subject->setUserResolver(fn() => $trooper);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_returns_empty_array(): void
    {
        $subject = new RequestDeletionRequest;

        $this->assertSame([], $subject->rules());
    }
}
