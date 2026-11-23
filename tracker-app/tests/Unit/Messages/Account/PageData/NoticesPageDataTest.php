<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Messages\Account\PageData\NoticesPageData;
use App\Messages\Notices\Queries\GetUnreadNotices;
use App\Models\Trooper;
use Illuminate\Support\Collection;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class NoticesPageDataTest extends TestCase
{
    use FakesMessages;

    public function test_handle_returns_the_troopers_unread_notices(): void
    {
        $trooper = Trooper::factory()->make();
        $notices = new Collection(['notice']);
        $fake = $this->fakeMessages([GetUnreadNotices::class => $notices]);

        $subject = new NoticesPageData($trooper);
        $result = $subject->handle();

        $this->assertSame(['notices' => $notices], $result);
        $fake->assertCalled(GetUnreadNotices::class, fn (array $params) => $params['trooper'] === $trooper);
    }
}
