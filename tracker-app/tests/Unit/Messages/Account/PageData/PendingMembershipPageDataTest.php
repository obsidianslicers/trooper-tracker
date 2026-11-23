<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Messages\Account\PageData\PendingMembershipPageData;
use Tests\TestCase;

class PendingMembershipPageDataTest extends TestCase
{
    public function test_handle_sends_nothing_because_the_page_reads_no_props(): void
    {
        $subject = new PendingMembershipPageData;

        $result = $subject->handle();

        $this->assertSame([], $result);
    }
}
