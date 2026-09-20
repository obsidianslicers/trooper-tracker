<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Troopers;

use App\Models\Trooper;
use App\Rules\Admin\Troopers\PendingTrooperMembership;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class PendingTrooperMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_trooper_model_is_pending(): void
    {
        $trooper = Trooper::factory()->asPending()->create();

        $validator = Validator::make(
            ['trooper' => $trooper],
            ['trooper' => [new PendingTrooperMembership()]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_passes_when_trooper_id_resolves_to_pending_trooper(): void
    {
        $trooper = Trooper::factory()->asPending()->create();

        $validator = Validator::make(
            ['trooper' => $trooper->id],
            ['trooper' => [new PendingTrooperMembership()]]
        );

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_trooper_model_is_not_pending(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $validator = Validator::make(
            ['trooper' => $trooper],
            ['trooper' => [new PendingTrooperMembership()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The trooper must have a pending membership status.',
            $validator->errors()->first('trooper')
        );
    }

    public function test_fails_when_trooper_id_resolves_to_non_pending_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $validator = Validator::make(
            ['trooper' => $trooper->id],
            ['trooper' => [new PendingTrooperMembership()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The trooper must have a pending membership status.',
            $validator->errors()->first('trooper')
        );
    }

    public function test_fails_when_trooper_id_cannot_be_found(): void
    {
        $validator = Validator::make(
            ['trooper' => 999999],
            ['trooper' => [new PendingTrooperMembership()]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The specified trooper could not be found.',
            $validator->errors()->first('trooper')
        );
    }
}
