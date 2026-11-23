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
            GetEventTroopersMissingCredit::class => ['rows' => collect([$this->row(1), $this->row(2)]), 'total' => 5],
        ]);

        $result = $this->resolve((new MissingCreditsPageData($actor, null, 2))->handle());

        $this->assertSame([1, 2], array_column($result['rows'], 'event_trooper_id'));
        $this->assertSame(4, $result['next_offset']);
        $this->assertTrue($result['has_more']);
        $this->assertNull($result['filtered_trooper']);
        $messages->assertCalled(
            GetEventTroopersMissingCredit::class,
            fn (array $params): bool => $params['offset'] === 2 && $params['trooper_id'] === null
        );
    }

    public function test_handle_sends_only_what_the_page_reads_of_each_row(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([
            GetEventTroopersMissingCredit::class => ['rows' => collect([$this->row(7)]), 'total' => 1],
        ]);

        $row = $this->resolve((new MissingCreditsPageData($actor))->handle())['rows'][0];

        $this->assertSame([
            'event_trooper_id' => 7,
            'trooper_id' => 70,
            'trooper_name' => 'TK-7',
            'event_name' => 'Library Day',
            'shift_label' => '10:00 AM - 2:00 PM',
            'costume_name' => 'Stormtrooper',
            'org_options' => [['id' => 3, 'name' => '501st']],
            'has_orphaned_db_value' => false,
            'fallback_org_options' => [],
            'trooper_has_no_club_membership' => false,
        ], $row);
    }

    public function test_handle_describes_the_filtered_trooper(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $trooper = Trooper::factory()->withDisplayName('TK-421')->create();
        $this->fakeMessages([
            GetEventTroopersMissingCredit::class => ['rows' => collect([$this->row(1)]), 'total' => 1],
        ]);

        $result = $this->resolve((new MissingCreditsPageData($actor, $trooper->id))->handle());

        $this->assertFalse($result['has_more']);
        $this->assertSame(['id' => $trooper->id, 'display_name' => 'TK-421'], $result['filtered_trooper']);
    }

    public function test_handle_returns_no_filtered_trooper_for_unknown_id(): void
    {
        $actor = Trooper::factory()->asSystemOwner()->create();
        $this->fakeMessages([
            GetEventTroopersMissingCredit::class => ['rows' => collect(), 'total' => 0],
        ]);

        $result = $this->resolve((new MissingCreditsPageData($actor, 999999))->handle());

        $this->assertNull($result['filtered_trooper']);
    }

    /**
     * A row as GetEventTroopersMissingCredit builds it, including the fields the page doesn't read.
     *
     * @return array<string, mixed>
     */
    private function row(int $event_trooper_id): array
    {
        return [
            'event_trooper_id' => $event_trooper_id,
            'trooper_id' => $event_trooper_id * 10,
            'trooper_name' => 'TK-'.$event_trooper_id,
            'event_id' => 99,
            'event_name' => 'Library Day',
            'shift_label' => '10:00 AM - 2:00 PM',
            'shift_starts_at' => '2026-06-01T10:00:00+00:00',
            'costume_name' => 'Stormtrooper',
            'org_options' => [['id' => 3, 'name' => '501st']],
            'has_eligible_options' => true,
            'has_orphaned_db_value' => false,
            'fallback_org_options' => [],
            'trooper_has_no_club_membership' => false,
        ];
    }

    /**
     * The payload as the page receives it, with Resources resolved.
     */
    private function resolve(array $data): array
    {
        return json_decode(json_encode($data), true);
    }
}
