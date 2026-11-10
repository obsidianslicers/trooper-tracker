<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Support\ForumBBCodeHelper;

class ForumBBCodeHelperTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->enableXenforo();
    }

    public function test_thread_template_includes_required_fields_and_sign_up_link(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[size=6][b]'.$event->name.'[/b][/size]', $result);
        $this->assertStringContainsString('[size=4][i]'.$event->venue.'[/i][/size]', $result);
        $this->assertStringContainsString('[b]📍 Location:[/b] '.$event->venue_address, $result);
        $this->assertStringContainsString('[b]🗓️ Schedule:[/b] Tue 03/10/26 02:30 PM – 06:00 PM', $result);
        $this->assertStringContainsString(
            '[center][size=6][url="'.url('/events/details/'.$event->id).'"]👉 CLICK HERE TO SIGN UP 👈[/url][/size][/center]',
            $result
        );
    }

    public function test_thread_template_shows_the_end_date_when_an_event_runs_past_one_day(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->state([Event::EVENT_END => now()->setDate(2026, 3, 11)->setTime(1, 0, 0)])
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[b]🗓️ Schedule:[/b] Tue 03/10/26 02:30 PM – Wed 03/11/26 01:00 AM', $result);
    }

    public function test_thread_template_shows_text_fields_as_written(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->withForumBbcodeEscapableText()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        //  XenForo escapes HTML itself, so only a "[" that could start a tag is escaped
        $this->assertStringContainsString(
            '[size=6][b][plain][[/plain]b]Imperial[plain][[/plain]/b] Muster & "Parade"[/b][/size]',
            $result
        );
        $this->assertStringContainsString('[size=4][i]Hall & Hangar[/i][/size]', $result);
        $this->assertStringContainsString('[b]📍 Location:[/b] 1 "Docking Bay" Lane', $result);
        $this->assertStringContainsString(
            '[b]Character Types:[/b] Jedi < Sith & [plain][[/plain]Bounty Hunters]',
            $result
        );
        $this->assertStringContainsString("[quote]Children's Hall & [plain][[/plain]Hangar][/quote]", $result);
        $this->assertStringContainsString(
            '[indent][size=4]Bring armor & "props" [plain][[/plain]if you have them].[/size][/indent]',
            $result
        );
        $this->assertStringContainsString("Referred by: O'Brien & [plain][[/plain]Sons]", $result);
        $this->assertStringNotContainsString('&amp;', $result);
        $this->assertStringNotContainsString('&quot;', $result);
        $this->assertStringNotContainsString('&#039;', $result);
    }

    public function test_thread_template_turns_markdown_comments_and_amenities_into_bbcode(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->state([
                Event::AMENITIES => 'Water **provided**',
                Event::COMMENTS => "Arrive **early**.\n\n| Time | What |\n|---|---|\n| 9:30 | Check in |",
            ])
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[quote]Water [b]provided[/b][/quote]', $result);
        $this->assertStringContainsString(
            "[indent][size=4]Arrive [b]early[/b].\n\n[table]\n"
            ."[tr][th]Time[/th][th]What[/th][/tr]\n"
            ."[tr][td]9:30[/td][td]Check in[/td][/tr]\n"
            .'[/table][/size][/indent]',
            $result
        );
        $this->assertStringNotContainsString('|', $result);
    }

    public function test_thread_template_says_no_comments_when_the_comments_are_only_markup(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->state([Event::COMMENTS => '<!-- nothing here -->', Event::AMENITIES => '   '])
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[indent][size=4]No additional comments provided.[/size][/indent]', $result);
        $this->assertStringNotContainsString('Amenities:', $result);
    }

    public function test_thread_template_links_a_website_url_without_escaping_it(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->state([Event::EVENT_WEBSITE => 'https://example.org/troop?faction=empire&rank=tk'])
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString(
            '[b]🔗 Website:[/b] [url="https://example.org/troop?faction=empire&rank=tk"]Click here to view[/url]',
            $result
        );
    }

    public function test_thread_template_shows_a_website_that_is_not_a_url_as_text(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->state([Event::EVENT_WEBSITE => 'see the "flyer" [attached]'])
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[b]🔗 Website:[/b] see the "flyer" [plain][[/plain]attached]', $result);
        $this->assertStringNotContainsString('Click here to view', $result);
    }

    public function test_thread_template_omits_optional_sections_when_values_are_missing(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->withForumBbcodeOptionalFieldsMissing()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringNotContainsString('Website:', $result);
        $this->assertStringNotContainsString('Attendees:', $result);
        $this->assertStringNotContainsString('Requested:', $result);
        $this->assertStringNotContainsString('Character Types:', $result);
        $this->assertStringNotContainsString('Amenities:', $result);
        $this->assertStringContainsString('[indent][size=4]No additional comments provided.[/size][/indent]', $result);
        $this->assertStringContainsString('[color=#777777][i]Referred by: N/A[/i][/color]', $result);
    }

    public function test_thread_template_puts_details_in_a_three_column_table(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString(
            "[table]\n"
            .'[tr][td][b]Attendees:[/b] 250[/td][td][b]Requested:[/b] 12[/td][td][b]Parking:[/b] Yes[/td][/tr]'."\n"
            .'[tr][td][b]Blasters:[/b] No[/td][td][b]Props/Sabers:[/b] Yes[/td][td][b]Accessible:[/b] No[/td][/tr]'."\n"
            .'[/table]',
            $result
        );
        $this->assertStringContainsString('[b]Secure Changing Area:[/b] Yes', $result);
    }

    public function test_thread_template_pads_the_last_table_row_when_details_are_missing(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->withForumBbcodeOptionalFieldsMissing()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString(
            '[tr][td][b]Parking:[/b] Yes[/td][td][b]Blasters:[/b] No[/td][td][b]Props/Sabers:[/b] Yes[/td][/tr]',
            $result
        );
        $this->assertStringContainsString('[tr][td][b]Accessible:[/b] No[/td][td][/td][td][/td][/tr]', $result);
    }

    public function test_thread_template_appends_roster_when_provided(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $roster = "[table][tr][td]TK-12345[/td][/tr]\n[/table]";

        $result = ForumBBCodeHelper::threadTemplate($event, $roster);

        $this->assertStringContainsString('[size=5][b]👥 Event Roster[/b][/size]'."\n".$roster, $result);
    }

    public function test_thread_template_leaves_out_the_roster_heading_without_a_roster(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringNotContainsString('Event Roster', $result);
    }

    public function test_roster_summary_returns_fallback_when_no_signups(): void
    {
        $event = Event::factory()->create();
        EventShift::factory()->forEvent($event)->create();

        $result = ForumBBCodeHelper::rosterSummary($event);

        $this->assertSame('[center]No troopers are signed up for this event.[/center]', $result);
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
            '[tr][td]'.$first_trooper->display_name.'[/td]',
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

        $table_head = '[table][tr][td][b]Trooper Name[/b][/td][td][b]Costume[/b][/td][td][b]Status[/b][/td][/tr]';
        $expected_earlier_section = '[b][u]'.$earlier_shift->time_display."[/u][/b]\n".$table_head."\n"
            .'[tr][td]'.$earlier_trooper->display_name.'[/td]';
        $expected_later_section = '[b][u]'.$later_shift->time_display."[/u][/b]\n".$table_head."\n"
            .'[tr][td]'.$later_trooper->display_name.'[/td]';

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
            '[b][u]'.$empty_shift->time_display."[/u][/b]\n[center]No troopers are signed up for this shift.[/center]",
            $result
        );
    }
}
