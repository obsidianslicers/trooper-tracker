<?php

declare(strict_types=1);

namespace Tests\Feature\Enums;

use App\Enums\EventGuestStatus;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class EventGuestStatusTest extends TestCase
{
    use DatabaseTransactions;

    public function test_to_select_array_excludes_stand_by(): void
    {
        $result = EventGuestStatus::toSelectArray();

        $this->assertArrayNotHasKey(EventGuestStatus::STAND_BY->value, $result);
    }

    public function test_to_select_array_includes_going_tentative_and_cancelled(): void
    {
        $result = EventGuestStatus::toSelectArray();

        $this->assertArrayHasKey(EventGuestStatus::GOING->value, $result);
        $this->assertArrayHasKey(EventGuestStatus::TENTATIVE->value, $result);
        $this->assertArrayHasKey(EventGuestStatus::CANCELLED->value, $result);
    }

    public function test_intent_to_go_array_includes_going_and_tentative_only(): void
    {
        $result = EventGuestStatus::intentToGoArray();

        $this->assertSame([
            EventGuestStatus::GOING,
            EventGuestStatus::TENTATIVE,
        ], $result);
    }

    public function test_intends_to_go_returns_expected_values_for_each_case(): void
    {
        $this->assertTrue(EventGuestStatus::GOING->intendsToGo());
        $this->assertTrue(EventGuestStatus::TENTATIVE->intendsToGo());

        $this->assertFalse(EventGuestStatus::STAND_BY->intendsToGo());
        $this->assertFalse(EventGuestStatus::CANCELLED->intendsToGo());
    }

    public function test_icon_returns_non_empty_string_for_each_case(): void
    {
        foreach (EventGuestStatus::cases() as $status)
        {
            $this->assertNotSame('', trim($status->icon()));
        }
    }

    public function test_color_returns_non_empty_string_for_each_case(): void
    {
        foreach (EventGuestStatus::cases() as $status)
        {
            $this->assertNotSame('', trim($status->color()));
        }
    }

    public function test_icon_tag_contains_icon_and_color_classes(): void
    {
        foreach (EventGuestStatus::cases() as $status)
        {
            $tag = $status->iconTag();

            $this->assertStringContainsString($status->icon(), $tag);
            $this->assertStringContainsString($status->color(), $tag);
            $this->assertStringStartsWith('<i class="fa fa-fw ', $tag);
        }
    }
}
