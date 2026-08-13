<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Search;

use App\Messages\Costumes\PageData\SearchCostumesPageData;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class SearchCostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('search.costumes'));

        $response->assertRedirect(route('auth.login'));
    }

    #[RunInSeparateProcess]
    public function test_invoke_returns_json_payload_from_search_costumes_page_data(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $payload = [
            [
                'id' => 101,
                'name' => 'Alpha Armor',
                'organizations' => [
                    ['id' => 1, 'name' => 'Core Worlds'],
                    ['id' => 2, 'name' => 'Outer Rim'],
                ],
            ],
            [
                'id' => 202,
                'name' => 'Bravo Armor',
                'organizations' => [
                    ['id' => 3, 'name' => 'Jedi Temple'],
                ],
            ],
        ];

        Mockery::mock('alias:' . SearchCostumesPageData::class)
            ->shouldReceive('call')
            ->once()
            ->withArgs(function (Request $request): bool
            {
                return $request->query('search_term') === 'Armor';
            })
            ->andReturn($payload);

        $response = $this->actingAs($trooper)
            ->get(route('search.costumes', ['search_term' => 'Armor']));

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
