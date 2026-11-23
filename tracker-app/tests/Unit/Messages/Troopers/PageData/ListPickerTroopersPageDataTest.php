<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\PageData;

use App\Messages\Troopers\PageData\ListPickerTroopersPageData;
use App\Messages\Troopers\Queries\GetTroopersForPicker;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ListPickerTroopersPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_returns_the_troopers_and_the_search(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troopers = collect(['TK-1']);
        $this->fakeMessages([GetTroopersForPicker::class => $troopers]);

        $subject = new ListPickerTroopersPageData(
            actor: $trooper,
            property: 'trooper_id',
            query: ['search_term' => 'vader', 'organization_id' => '7', 'event' => 'Parade'],
        );
        $result = $subject->handle(app(TrooperFilter::class));

        $this->assertSame([
            'troopers' => $troopers,
            'property' => 'trooper_id',
            'search_term' => 'vader',
            'organization_id' => 7,
            'event_name' => 'Parade',
        ], $result);
    }

    public function test_handle_leaves_out_what_the_picker_did_not_ask_for(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->fakeMessages([GetTroopersForPicker::class => collect()]);

        $subject = new ListPickerTroopersPageData(actor: $trooper, property: 'trooper_id');
        $result = $subject->handle(app(TrooperFilter::class));

        $this->assertNull($result['search_term']);
        $this->assertNull($result['organization_id']);
        $this->assertSame('', $result['event_name']);
    }
}
