<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Search;

use App\Messages\Troopers\PageData\SearchTroopersPageData;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class SearchTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('search.troopers'));

        $response->assertRedirect(route('auth.login'));
    }

    #[RunInSeparateProcess]
    public function test_invoke_returns_json_payload_from_search_troopers_page_data(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $payload = [
            [
                Trooper::ID => 101,
                Trooper::DISPLAY_NAME => 'Alpha Trooper',
            ],
            [
                Trooper::ID => 202,
                Trooper::DISPLAY_NAME => 'Bravo Trooper',
            ],
        ];

        Mockery::mock('alias:' . SearchTroopersPageData::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(function (Request $request): bool
            {
                return $request->query('search_term') === 'Trooper';
            })
            ->andReturn($payload);

        $response = $this->actingAs($trooper)
            ->get(route('search.troopers', ['search_term' => 'Trooper']));

        $response->assertOk();
        $response->assertJson($payload);
        $response->assertHeader('content-type', 'application/json');
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
