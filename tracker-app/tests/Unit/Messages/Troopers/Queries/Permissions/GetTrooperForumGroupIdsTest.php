<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Permissions;

use App\Messages\Troopers\Queries\Permissions\GetTrooperForumGroupIds;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GetTrooperForumGroupIdsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_null_when_xenforo_is_not_configured(): void
    {
        config(['services.xenforo.base_url' => '', 'services.xenforo.api_key' => null]);
        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldNotReceive('resolve_user_id_for_trooper');

        $subject = new GetTrooperForumGroupIds(Trooper::factory()->create());

        $this->assertNull($subject->handle($xenforo));
    }

    public function test_handle_returns_null_when_trooper_has_no_forum_account(): void
    {
        $this->configureXenforo();
        $trooper = Trooper::factory()->create();
        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')->once()->with($trooper->id)->andReturn(null);
        $xenforo->shouldNotReceive('getUserSecondaryGroupIds');

        $this->assertNull((new GetTrooperForumGroupIds($trooper))->handle($xenforo));
    }

    public function test_handle_returns_secondary_group_ids_for_forum_user(): void
    {
        $this->configureXenforo();
        $trooper = Trooper::factory()->create();
        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')->once()->with($trooper->id)->andReturn(77);
        $xenforo->shouldReceive('getUserSecondaryGroupIds')->once()->with(77)->andReturn([3, 9]);

        $this->assertSame([3, 9], (new GetTrooperForumGroupIds($trooper))->handle($xenforo));
    }

    private function configureXenforo(): void
    {
        config(['services.xenforo.base_url' => 'https://forums.example.com', 'services.xenforo.api_key' => 'key']);
    }
}
