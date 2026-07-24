<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use App\Http\Middleware\HandleInertiaRequests;
use App\Messages\App\Queries\GetConfig;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class HandleInertiaRequestsTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    public function test_share_returns_guest_props_and_aggregated_flash_messages(): void
    {
        $config = [
            'branding' => ['name' => 'Troop Tracker'],
            'meta' => ['env' => 'testing'],
        ];

        Mockery::mock('alias:' . GetConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn($config);

        Session::put('flash_messages', [
            'info' => ['Custom info'],
            'warning' => ['Custom warning'],
        ]);

        $request = Request::create('/');
        $request->setLaravelSession(Session::driver());
        $request->session()->put('results', ['status' => 'ok']);
        $request->session()->flash('success', 'Saved');
        $request->session()->flash('danger', 'Failed');

        $subject = new HandleInertiaRequests();
        $shared = $subject->share($request);

        $this->assertSame($config, $shared['config']);
        $this->assertSame([
            Trooper::ID => null,
            Trooper::LEGAL_NAME => null,
            Trooper::DISPLAY_NAME => null,
            Trooper::EMAIL => null,
        ], $shared['user']);
        $this->assertSame(['status' => 'ok'], $shared['results']());
        $this->assertSame([
            'success' => ['Saved'],
            'danger' => ['Failed'],
            'info' => ['Custom info'],
            'warning' => ['Custom warning'],
        ], $shared['flash']());
        $this->assertSame([], Session::get('flash_messages', []));

        Mockery::close();
    }

    #[RunInSeparateProcess]
    public function test_share_returns_authenticated_trooper_props(): void
    {
        $config = ['meta' => ['env' => 'testing']];

        Mockery::mock('alias:' . GetConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn($config);

        $trooper = Trooper::factory()->asActive()->create([
            Trooper::LEGAL_NAME => 'TK Test',
            Trooper::DISPLAY_NAME => 'TK-421',
            Trooper::EMAIL => 'tk421@example.com',
        ]);

        $this->actingAs($trooper);

        $request = Request::create('/');
        $request->setLaravelSession(Session::driver());
        $request->setUserResolver(static fn(): Trooper => $trooper);

        $subject = new HandleInertiaRequests();
        $shared = $subject->share($request);

        $this->assertSame($config, $shared['config']);
        $this->assertSame([
            Trooper::ID => $trooper->id,
            Trooper::LEGAL_NAME => 'TK Test',
            Trooper::DISPLAY_NAME => 'TK-421',
            Trooper::EMAIL => 'tk421@example.com',
        ], $shared['user']);

        Mockery::close();
    }
}