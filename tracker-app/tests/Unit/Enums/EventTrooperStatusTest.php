<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\EventTrooperStatus;
use Tests\TestCase;

class EventTrooperStatusTest extends TestCase
{
    public function test_to_sign_up_array_includes_required_statuses(): void
    {
        $result = EventTrooperStatus::toSignUpArray(false);

        $this->assertArrayHasKey(EventTrooperStatus::GOING->value, $result);
        $this->assertArrayHasKey(EventTrooperStatus::STAND_BY->value, $result);
        $this->assertArrayHasKey(EventTrooperStatus::CANCELLED->value, $result);
    }

    public function test_to_sign_up_array_excludes_tentative_when_not_allowed(): void
    {
        $result = EventTrooperStatus::toSignUpArray(false);

        $this->assertArrayNotHasKey(EventTrooperStatus::TENTATIVE->value, $result);
        $this->assertCount(3, $result);
    }

    public function test_to_sign_up_array_includes_tentative_when_allowed(): void
    {
        $result = EventTrooperStatus::toSignUpArray(true);

        $this->assertArrayHasKey(EventTrooperStatus::TENTATIVE->value, $result);
        $this->assertCount(4, $result);
    }

    public function test_to_sign_up_array_returns_formatted_titles(): void
    {
        $result = EventTrooperStatus::toSignUpArray(false);

        $this->assertStringContainsString('Going', (string) $result[EventTrooperStatus::GOING->value]);
        $this->assertStringContainsString('Stand', (string) $result[EventTrooperStatus::STAND_BY->value]);
        $this->assertStringContainsString('Cancelled', (string) $result[EventTrooperStatus::CANCELLED->value]);
    }

    public function test_icon_returns_valid_font_awesome_class(): void
    {
        foreach (EventTrooperStatus::cases() as $status)
        {
            $icon = $status->icon();
            $this->assertStringStartsWith('fa-', $icon, sprintf(
                '%s icon should start with fa-',
                $status->name
            ));
        }
    }

    public function test_icon_returns_correct_class_for_each_status(): void
    {
        $this->assertEquals('fa-circle-question', EventTrooperStatus::NONE->icon());
        $this->assertEquals('fa-circle-play', EventTrooperStatus::GOING->icon());
        $this->assertEquals('fa-circle-pause', EventTrooperStatus::STAND_BY->icon());
        $this->assertEquals('fa-circle-dot', EventTrooperStatus::TENTATIVE->icon());
        $this->assertEquals('fa-user-check', EventTrooperStatus::ATTENDED->icon());
        $this->assertEquals('fa-times-circle', EventTrooperStatus::CANCELLED->icon());
        $this->assertEquals('fa-hourglass-half', EventTrooperStatus::PENDING->icon());
        $this->assertEquals('fa-ban', EventTrooperStatus::NOT_PICKED->icon());
        $this->assertEquals('fa-user-slash', EventTrooperStatus::NO_SHOW->icon());
        $this->assertEquals('fa-circle-xmark', EventTrooperStatus::UNABLE_TO_ATTEND->icon());
    }

    public function test_color_returns_valid_bootstrap_class(): void
    {
        foreach (EventTrooperStatus::cases() as $status)
        {
            $color = $status->color();
            $this->assertStringStartsWith('text-', $color, sprintf(
                '%s color should start with text-',
                $status->name
            ));
        }
    }

    public function test_color_returns_correct_class_for_each_status(): void
    {
        $this->assertEquals('text-muted', EventTrooperStatus::NONE->color());
        $this->assertEquals('text-success', EventTrooperStatus::GOING->color());
        $this->assertEquals('text-warning', EventTrooperStatus::STAND_BY->color());
        $this->assertEquals('text-warning', EventTrooperStatus::TENTATIVE->color());
        $this->assertEquals('text-success', EventTrooperStatus::ATTENDED->color());
        $this->assertEquals('text-danger', EventTrooperStatus::CANCELLED->color());
        $this->assertEquals('text-info', EventTrooperStatus::PENDING->color());
        $this->assertEquals('text-secondary', EventTrooperStatus::NOT_PICKED->color());
        $this->assertEquals('text-muted', EventTrooperStatus::NO_SHOW->color());
        $this->assertEquals('text-danger', EventTrooperStatus::UNABLE_TO_ATTEND->color());
    }

    public function test_icon_tag_returns_html_formatted_string(): void
    {
        $result = EventTrooperStatus::GOING->iconTag();

        $this->assertStringStartsWith('<i', $result);
        $this->assertStringEndsWith('</i>', $result);
        $this->assertStringContainsString('fa fa-fw', $result);
        $this->assertStringContainsString('ms-2', $result);
    }

    public function test_icon_tag_includes_icon_and_color_classes(): void
    {
        $result = EventTrooperStatus::GOING->iconTag();

        $this->assertStringContainsString('fa-circle-play', $result);
        $this->assertStringContainsString('text-success', $result);
    }

    public function test_icon_tag_is_safe_for_blade_output(): void
    {
        $result = EventTrooperStatus::ATTENDED->iconTag();

        // Should contain proper HTML structure safe for {!! !!} output
        $this->assertMatchesRegularExpression('/<i[^>]*>/', $result);
        $this->assertStringContainsString('class=', $result);
    }

    public function test_different_statuses_generate_different_icon_tags(): void
    {
        $going_tag = EventTrooperStatus::GOING->iconTag();
        $cancelled_tag = EventTrooperStatus::CANCELLED->iconTag();

        $this->assertNotEquals($going_tag, $cancelled_tag);
        $this->assertStringContainsString('fa-circle-play', $going_tag);
        $this->assertStringContainsString('fa-times-circle', $cancelled_tag);
    }
}
