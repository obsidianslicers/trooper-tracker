<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Troopers;

use App\Models\TrooperRequest;
use App\Rules\Admin\Troopers\PendingTrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PendingTrooperRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_trooper_request_model_is_pending(): void
    {
        $trooper_request = TrooperRequest::factory()->asPending()->create();

        $validator = Validator::make(
            ['trooper_request' => $trooper_request],
            ['trooper_request' => [new PendingTrooperRequest()]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_passes_when_trooper_request_id_resolves_to_pending_request(): void
    {
        $trooper_request = TrooperRequest::factory()->asPending()->create();

        $validator = Validator::make(
            ['trooper_request' => $trooper_request->id],
            ['trooper_request' => [new PendingTrooperRequest()]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_trooper_request_model_is_not_pending(): void
    {
        $trooper_request = TrooperRequest::factory()->asApproved()->create();

        $validator = Validator::make(
            ['trooper_request' => $trooper_request],
            ['trooper_request' => [new PendingTrooperRequest()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The trooper request must have a pending status.',
            $validator->errors()->first('trooper_request')
        );
    }

    public function test_fails_when_trooper_request_id_resolves_to_non_pending_request(): void
    {
        $trooper_request = TrooperRequest::factory()->asApproved()->create();

        $validator = Validator::make(
            ['trooper_request' => $trooper_request->id],
            ['trooper_request' => [new PendingTrooperRequest()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The trooper request must have a pending status.',
            $validator->errors()->first('trooper_request')
        );
    }

    public function test_fails_when_trooper_request_id_cannot_be_found(): void
    {
        $validator = Validator::make(
            ['trooper_request' => 999999],
            ['trooper_request' => [new PendingTrooperRequest()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The specified trooper request could not be found.',
            $validator->errors()->first('trooper_request')
        );
    }
}
