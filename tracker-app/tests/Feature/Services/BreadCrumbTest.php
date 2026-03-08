<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\BreadCrumb;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BreadCrumbTest extends TestCase
{
    use DatabaseTransactions;

    public function test_construct_sets_title_and_default_url(): void
    {
        $subject = new BreadCrumb('Dashboard');

        $this->assertSame('Dashboard', $subject->title);
        $this->assertSame('', $subject->url);
    }

    public function test_construct_sets_title_and_url(): void
    {
        $subject = new BreadCrumb('Events', '/events');

        $this->assertSame('Events', $subject->title);
        $this->assertSame('/events', $subject->url);
    }
}
