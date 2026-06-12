<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ForumBBCodeHelper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ForumBBCodeHelperTest extends TestCase
{
    use RefreshDatabase;

    public function test_thread_template_includes_required_fields_and_sign_up_link(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[b]Event Name:[/b] '.e($event->name), $result);
        $this->assertStringContainsString('[b]Venue:[/b] '.e($event->venue), $result);
        $this->assertStringContainsString('[b]Venue address:[/b] '.e($event->venue_address), $result);
        $this->assertStringContainsString(
            '[b]Event Start:[/b] '.$event->event_start->format('m/d/y h:i A'),
            $result
        );
        $this->assertStringContainsString(
            '[b]Event End:[/b] '.$event->event_end->format('m/d/y h:i A'),
            $result
        );
        $this->assertStringContainsString('[b][u]Sign Up / Event Roster:[/u][/b]', $result);
        $this->assertStringContainsString('[url]'.url('/events/details/'.$event->id).'[/url]', $result);
    }

    public function test_thread_template_escapes_bbcode_text_fields(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->withForumBbcodeEscapableText()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[b]Event Name:[/b] '.e($event->name), $result);
        $this->assertStringContainsString('[b]Venue:[/b] '.e($event->venue), $result);
        $this->assertStringContainsString('[b]Venue address:[/b] '.e($event->venue_address), $result);
        $this->assertStringContainsString('[b]Event Website:[/b] '.e($event->event_website), $result);
        $this->assertStringContainsString(
            '[b]Requested character types:[/b] '.e($event->requested_character_types),
            $result
        );
        $this->assertStringContainsString('[b]Amenities available at venue:[/b] '.e($event->amenities), $result);
        $this->assertStringContainsString('[b]Comments:[/b]'."\n".e($event->comments), $result);
    }

    public function test_thread_template_omits_optional_sections_when_values_are_missing(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->withForumBbcodeOptionalFieldsMissing()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringNotContainsString('[b]Event Website:[/b]', $result);
        $this->assertStringNotContainsString('[b]Expected number of attendees:[/b]', $result);
        $this->assertStringNotContainsString('[b]Requested number of characters:[/b]', $result);
        $this->assertStringNotContainsString('[b]Requested character types:[/b]', $result);
        $this->assertStringNotContainsString('[b]Amenities available at venue:[/b]', $result);
        $this->assertStringContainsString('[b]Comments:[/b]'."\n".'No comments for this event.', $result);
        $this->assertStringContainsString('[b]Referred by:[/b] Not available', $result);
    }

    public function test_thread_template_renders_boolean_sections_as_yes_or_no(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[b]Secure changing/staging area:[/b] Yes', $result);
        $this->assertStringContainsString('[b]Can troopers carry blasters:[/b] No', $result);
        $this->assertStringContainsString(
            '[b]Can troopers carry/bring props like lightsabers and staffs:[/b] Yes',
            $result
        );
        $this->assertStringContainsString('[b]Is parking available:[/b] Yes', $result);
        $this->assertStringContainsString(
            '[b]Is venue accessible to those with limited mobility:[/b] No',
            $result
        );
    }

    public function test_thread_template_appends_roster_when_provided(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $roster = "[b]Roster[/b]\n- TK-12345\n- DZ-54321";

        $result = ForumBBCodeHelper::threadTemplate($event, $roster);

        $this->assertStringContainsString($roster, $result);
    }

    public function test_roster_summary_returns_fallback_when_no_signups(): void
    {
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        $result = ForumBBCodeHelper::rosterSummary($event);

        $this->assertSame(
            "[b]Roster:[/b]\n-No troopers are signed up for this event.",
            $result
        );
    }

    public function test_roster_summary_lists_single_shift_signups_without_shift_headers(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $first_trooper = Trooper::factory()->create();
        $second_trooper = Trooper::factory()->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($second_trooper)
            ->asGoing()
            ->withSignedUpAt(Carbon::now()->subHour())
            ->create();
        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($first_trooper)
            ->asGoing()
            ->withSignedUpAt(Carbon::now()->subHours(2))
            ->create();

        $result = ForumBBCodeHelper::rosterSummary($event);

        $this->assertStringNotContainsString($shift->time_display, $result);
        $this->assertStringContainsString(
            '-[i]Going[/i]: '.$first_trooper->display_name,
            $result
        );

        $first_position = strpos($result, $first_trooper->display_name);
        $second_position = strpos($result, $second_trooper->display_name);
        $this->assertLessThan($second_position, $first_position);
    }

    public function test_roster_summary_groups_signups_by_shift_with_headers(): void
    {
        $event = Event::factory()->create();

        $later_shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-07-11 12:00'))
            ->withShiftEndsAt(Carbon::parse('2026-07-11 16:00'))
            ->create();
        $earlier_shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-07-10 12:00'))
            ->withShiftEndsAt(Carbon::parse('2026-07-10 16:00'))
            ->create();

        $earlier_trooper = Trooper::factory()->create();
        $later_trooper = Trooper::factory()->create();

        EventTrooper::factory()
            ->forEventShift($earlier_shift)
            ->forTrooper($earlier_trooper)
            ->asGoing()
            ->withSignedUpAt(Carbon::now())
            ->create();
        EventTrooper::factory()
            ->forEventShift($later_shift)
            ->forTrooper($later_trooper)
            ->asGoing()
            ->withSignedUpAt(Carbon::now())
            ->create();

        $result = ForumBBCodeHelper::rosterSummary($event);

        $expected_earlier_section = '[b][u]'.$earlier_shift->time_display."[/u][/b]\n"
            .'-[i]Going[/i]: '.$earlier_trooper->display_name;
        $expected_later_section = '[b][u]'.$later_shift->time_display."[/u][/b]\n"
            .'-[i]Going[/i]: '.$later_trooper->display_name;

        $this->assertStringContainsString($expected_earlier_section, $result);
        $this->assertStringContainsString($expected_later_section, $result);

        $earlier_position = strpos($result, $expected_earlier_section);
        $later_position = strpos($result, $expected_later_section);
        $this->assertLessThan($later_position, $earlier_position);
    }

    public function test_roster_summary_notes_empty_shifts_in_multi_shift_events(): void
    {
        $event = Event::factory()->create();

        $filled_shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-07-10 12:00'))
            ->withShiftEndsAt(Carbon::parse('2026-07-10 16:00'))
            ->create();
        $empty_shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(Carbon::parse('2026-07-11 12:00'))
            ->withShiftEndsAt(Carbon::parse('2026-07-11 16:00'))
            ->create();

        EventTrooper::factory()
            ->forEventShift($filled_shift)
            ->forTrooper(Trooper::factory()->create())
            ->asGoing()
            ->withSignedUpAt(Carbon::now())
            ->create();

        $result = ForumBBCodeHelper::rosterSummary($event);

        $this->assertStringContainsString(
            '[b][u]'.$empty_shift->time_display."[/u][/b]\n-No troopers are signed up for this shift.",
            $result
        );
    }
}
