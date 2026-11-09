<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\ServiceRecords\PageData;

use App\Messages\ServiceRecords\PageData\MissingCreditsPageData;
use App\Messages\Troopers\Queries\GetEventTroopersMissingCredit;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class MissingCreditsPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_pages_rows_and_reports_more_remaining(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $messages = $this->fakeMessages([
            GetEventTroopersMissingCredit::class => ['rows' => collect([['id' => 1], ['id' => 2]]), 'total' => 5],
        ]);

        $result = (new MissingCreditsPageData($actor, null, 2))->handle();

        $this->assertSame([['id' => 1], ['id' => 2]], $result['rows']);
        $this->assertSame(4, $result['next_offset']);
        $this->assertTrue($result['has_more']);
        $this->assertNull($result['filtered_trooper']);
        $messages->assertCalled(
            GetEventTroopersMissingCredit::class,
            fn (array $params): bool => $params['offset'] === 2 && $params['trooper_id'] === null
        );
    }

    public function test_handle_describes_the_filtered_trooper(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $trooper = Trooper::factory()->withDisplayName('TK-421')->create();
        $this->fakeMessages([
            GetEventTroopersMissingCredit::class => ['rows' => collect([['id' => 1]]), 'total' => 1],
        ]);

        $result = (new MissingCreditsPageData($actor, $trooper->id))->handle();

        $this->assertFalse($result['has_more']);
        $this->assertSame(['id' => $trooper->id, 'display_name' => 'TK-421'], $result['filtered_trooper']);
    }

    public function test_handle_returns_no_filtered_trooper_for_unknown_id(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([
            GetEventTroopersMissingCredit::class => ['rows' => collect(), 'total' => 0],
        ]);

        $result = (new MissingCreditsPageData($actor, 999999))->handle();

        $this->assertNull($result['filtered_trooper']);
    }
}
