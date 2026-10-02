<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Messages\Account\PageData\PendingMembershipPageData;
use App\Messages\Account\Resources\PendingTrooper;
use App\Models\Trooper;
use Tests\TestCase;

class PendingMembershipPageDataTest extends TestCase
{
    public function test_handle_returns_pending_trooper_data(): void
    {
        $trooper = Trooper::factory()->make([
            Trooper::LEGAL_NAME => 'Leia Organa',
            Trooper::DISPLAY_NAME => 'Leia',
        ]);
        $subject = new PendingMembershipPageData($trooper);

        $result = $subject->handle();

        $this->assertArrayHasKey('trooper', $result);
        $this->assertInstanceOf(PendingTrooper::class, $result['trooper']);
        $this->assertSame([
            Trooper::LEGAL_NAME => 'Leia Organa',
            Trooper::DISPLAY_NAME => 'Leia',
        ], $result['trooper']->resolve());
    }
}