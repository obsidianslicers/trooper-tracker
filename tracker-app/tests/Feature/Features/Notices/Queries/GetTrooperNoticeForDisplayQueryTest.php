<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Notices\Queries;

use App\Features\Notices\Queries\GetTrooperNoticeForDisplayQuery;
use App\Models\Trooper;
use Tests\TestCase;

class GetTrooperNoticeForDisplayQueryTest extends TestCase
{
    public function test_construct_stores_trooper_and_defaults_unread_only_to_false(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperNoticeForDisplayQuery($trooper);

        $this->assertSame($trooper, $subject->trooper);
        $this->assertFalse($subject->unread_only);
    }

    public function test_construct_accepts_unread_only_parameter(): void
    {
        $trooper = new Trooper();

        $subject = new GetTrooperNoticeForDisplayQuery($trooper, true);

        $this->assertTrue($subject->unread_only);
    }
}
