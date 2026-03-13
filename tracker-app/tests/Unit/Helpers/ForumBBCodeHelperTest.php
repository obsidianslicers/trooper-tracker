<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\ForumBBCodeHelper;
use App\Models\Event;
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

        $this->assertStringContainsString('[b]Event Name:[/b] ' . e($event->name), $result);
        $this->assertStringContainsString('[b]Venue:[/b] ' . e($event->venue), $result);
        $this->assertStringContainsString('[b]Venue address:[/b] ' . e($event->venue_address), $result);
        $this->assertStringContainsString(
            '[b]Event Start:[/b] ' . $event->event_start->format('m/d/y h:i A'),
            $result
        );
        $this->assertStringContainsString(
            '[b]Event End:[/b] ' . $event->event_end->format('m/d/y h:i A'),
            $result
        );
        $this->assertStringContainsString('[b][u]Sign Up / Event Roster:[/u][/b]', $result);
        $this->assertStringContainsString('[url]' . url('/events/' . $event->id) . '[/url]', $result);
    }

    public function test_thread_template_escapes_bbcode_text_fields(): void
    {
        $event = Event::factory()
            ->forForumBbcodeTemplate()
            ->withForumBbcodeEscapableText()
            ->create();

        $result = ForumBBCodeHelper::threadTemplate($event);

        $this->assertStringContainsString('[b]Event Name:[/b] ' . e($event->name), $result);
        $this->assertStringContainsString('[b]Venue:[/b] ' . e($event->venue), $result);
        $this->assertStringContainsString('[b]Venue address:[/b] ' . e($event->venue_address), $result);
        $this->assertStringContainsString('[b]Event Website:[/b] ' . e($event->event_website), $result);
        $this->assertStringContainsString(
            '[b]Requested character types:[/b] ' . e($event->requested_character_types),
            $result
        );
        $this->assertStringContainsString('[b]Amenities available at venue:[/b] ' . e($event->amenities), $result);
        $this->assertStringContainsString('[b]Comments:[/b]' . "\n" . e($event->comments), $result);
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
        $this->assertStringContainsString('[b]Comments:[/b]' . "\n" . 'No comments for this event.', $result);
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
}
