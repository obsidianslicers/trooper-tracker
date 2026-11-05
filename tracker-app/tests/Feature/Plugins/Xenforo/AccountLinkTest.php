<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;

class AccountLinkTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
        Route::getRoutes()->refreshNameLookups();
    }

    public function test_unlinked_trooper_is_sent_to_link_page_when_required(): void
    {
        config(['tracker.auth.require_xenforo' => true]);
        $trooper = Trooper::factory()->asActive()->create();

        $this->actingAs($trooper)->get(route('account.index'))
            ->assertRedirect(route('account.xenforo.required'));

        $this->actingAs($trooper)->get(route('account.xenforo.required'))
            ->assertOk()
            ->assertSee('XenForo account required');
    }

    public function test_link_page_is_reachable_before_setup_is_finished(): void
    {
        config(['tracker.auth.require_xenforo' => true]);
        $trooper = Trooper::factory()->asActive()->withSetupIncomplete()->create();

        $this->actingAs($trooper)->get(route('account.index'))
            ->assertRedirect(route('account.setup'));

        $this->actingAs($trooper)->get(route('account.xenforo.required'))->assertOk();
    }

    public function test_unlinked_trooper_is_let_through_when_not_required(): void
    {
        config(['tracker.auth.require_xenforo' => false]);
        $trooper = Trooper::factory()->asActive()->create();

        $this->actingAs($trooper)->get(route('account.index'))->assertOk();
    }

    public function test_banned_forum_user_is_logged_out(): void
    {
        config(['tracker.auth.require_xenforo' => true]);
        $trooper = Trooper::factory()->asActive()->create();
        OauthLogin::create([
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => XenforoLoginProvider::KEY,
            OauthLogin::PROVIDER_ID => '55',
        ]);
        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('resolve_user_id_for_trooper')->andReturn(55);
        $xenforo->shouldReceive('get_user')->andReturn([
            'status' => 200,
            'body' => ['user' => ['is_banned' => true]],
        ]);
        $this->app->instance(XenforoService::class, $xenforo);

        $this->actingAs($trooper)->get(route('account.index'))
            ->assertRedirect(route('auth.login'))
            ->assertSessionHasErrors('banned');

        $this->assertGuest();
    }

    public function test_link_status_page_shows_the_linked_account(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $this->actingAs($trooper)->get(route('account.xenforo.index'))->assertOk();
    }
}
