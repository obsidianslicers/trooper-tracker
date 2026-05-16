<?php

declare(strict_types=1);

namespace Tests\Feature\Features\ServiceRecords\Queries;

use App\Features\ServiceRecords\Queries\GetPhotoGalleryQuery;
use App\Features\ServiceRecords\Queries\GetPhotoGalleryQueryHandler;
use App\Models\Event;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class GetPhotoGalleryQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    private GetPhotoGalleryQueryHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new GetPhotoGalleryQueryHandler();
    }

    public function test_invoke_returns_paginator(): void
    {
        $result = ($this->subject)(new GetPhotoGalleryQuery());

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
    }

    public function test_invoke_excludes_administrative_uploads(): void
    {
        $event = Event::factory()->create();

        EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);
        EventUpload::factory()->for($event)->create([EventUpload::IS_ADMINISTRATIVE => true]);

        $result = ($this->subject)(new GetPhotoGalleryQuery());

        $this->assertCount(1, $result->items());
        $this->assertFalse($result->items()[0]->is_administrative);
    }

    public function test_invoke_orders_by_most_recent_first(): void
    {
        $event = Event::factory()->create();

        $older = EventUpload::factory()->for($event)->create([
            EventUpload::IS_ADMINISTRATIVE => false,
            EventUpload::CREATED_AT => now()->subDays(2),
        ]);

        $newer = EventUpload::factory()->for($event)->create([
            EventUpload::IS_ADMINISTRATIVE => false,
            EventUpload::CREATED_AT => now()->subDays(1),
        ]);

        $result = ($this->subject)(new GetPhotoGalleryQuery());

        $this->assertSame($newer->id, $result->items()[0]->id);
        $this->assertSame($older->id, $result->items()[1]->id);
    }

    public function test_invoke_eager_loads_event_trooper_and_troopers(): void
    {
        $event = Event::factory()->create();
        $uploader = Trooper::factory()->create();
        $tagged = Trooper::factory()->create();

        $upload = EventUpload::factory()
            ->for($event)
            ->for($uploader, 'trooper')
            ->create([EventUpload::IS_ADMINISTRATIVE => false]);

        EventUploadTrooper::factory()->forEventUpload($upload)->forTrooper($tagged)->create();

        $result = ($this->subject)(new GetPhotoGalleryQuery());

        $item = $result->items()[0];
        $this->assertTrue($item->relationLoaded('event'));
        $this->assertTrue($item->relationLoaded('trooper'));
        $this->assertTrue($item->relationLoaded('troopers'));
        $this->assertSame($event->id, $item->event->id);
        $this->assertSame($uploader->id, $item->trooper->id);
        $this->assertCount(1, $item->troopers);
        $this->assertSame($tagged->id, $item->troopers->first()->id);
    }

    public function test_invoke_paginates_at_24_per_page(): void
    {
        $event = Event::factory()->create();

        EventUpload::factory(30)->for($event)->create([EventUpload::IS_ADMINISTRATIVE => false]);

        $result = ($this->subject)(new GetPhotoGalleryQuery());

        $this->assertSame(24, $result->perPage());
        $this->assertCount(24, $result->items());
        $this->assertSame(30, $result->total());
    }
}
