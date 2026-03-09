<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\BreadCrumb;
use App\Services\BreadCrumbService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class BreadCrumbServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_has_crumbs_returns_false_when_empty(): void
    {
        $subject = new BreadCrumbService;

        $this->assertFalse($subject->hasCrumbs());
        $this->assertSame([], $subject->getCrumbs());
    }

    public function test_add_creates_plain_crumb(): void
    {
        $subject = new BreadCrumbService;

        $subject->add('Dashboard');

        $crumbs = $subject->getCrumbs();

        $this->assertTrue($subject->hasCrumbs());
        $this->assertCount(1, $crumbs);
        $this->assertInstanceOf(BreadCrumb::class, $crumbs[0]);
        $this->assertSame('Dashboard', $crumbs[0]->title);
        $this->assertSame('', $crumbs[0]->url);
    }

    public function test_add_route_creates_linked_crumb(): void
    {
        $subject = new BreadCrumbService;

        $subject->addRoute('Events', 'events.list');

        $crumbs = $subject->getCrumbs();

        $this->assertCount(1, $crumbs);
        $this->assertSame('Events', $crumbs[0]->title);
        $this->assertSame(route('events.list'), $crumbs[0]->url);
    }
}
