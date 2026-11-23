<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\PageData;

use App\Messages\Organizations\PageData\ListPickerOrganizationsPageData;
use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class ListPickerOrganizationsPageDataTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_handle_passes_the_picker_options_to_the_query(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organizations = collect(['club']);
        $fake = $this->fakeMessages([GetOrganizationsForPicker::class => $organizations]);

        $subject = new ListPickerOrganizationsPageData(
            actor: $trooper,
            property: 'organization_id',
            query: ['moderated_only' => '1'],
        );
        $result = $subject->handle();

        $this->assertSame(['organizations' => $organizations, 'property' => 'organization_id'], $result);
        $fake->assertCalled(
            GetOrganizationsForPicker::class,
            fn (array $params): bool => $params['trooper'] === $trooper && $params['data'] === ['moderated_only' => '1']
        );
    }
}
