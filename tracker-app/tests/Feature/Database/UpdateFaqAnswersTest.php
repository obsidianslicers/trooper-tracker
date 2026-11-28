<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Faq;
use App\Models\FaqSection;
use Database\Seeders\FaqSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Database\Fixtures\FaqBefore20261125Seeder;
use Tests\TestCase;

class UpdateFaqAnswersTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_turns_the_seeded_faq_into_what_the_seeder_writes_now(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);

        $this->migration()->up();
        //  the videos changed after this migration, in one of their own
        (require database_path('migrations/2026_11_28_000001_update_faq_videos.php'))->up();
        $migrated = $this->snapshot();

        Faq::withTrashed()->forceDelete();
        FaqSection::withTrashed()->forceDelete();
        $this->seed(FaqSeeder::class);

        $this->assertSame($this->snapshot(), $migrated);
    }

    public function test_up_twice_changes_nothing_more(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);
        $this->migration()->up();
        $once = $this->snapshot();

        $this->migration()->up();

        $this->assertSame($once, $this->snapshot());
    }

    public function test_up_keeps_answers_command_staff_edited(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);
        $edited = $this->item('How do I withdraw from a shift?');
        $edited->update([Faq::DESCRIPTION => 'Ask your squad leader.']);
        $kept = $this->item('I need a costume added to the Troop Tracker.');
        $kept->update([Faq::DESCRIPTION => 'Email the costume officer.']);

        $this->migration()->up();

        $this->assertSame('Ask your squad leader.', $edited->fresh()->description);
        $this->assertNotNull($kept->fresh());
    }

    public function test_up_does_not_add_a_question_the_faq_already_has(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);
        $events = FaqSection::where(FaqSection::LABEL, 'Events')->first();
        Faq::factory()->create([
            Faq::SECTION_ID => $events->id,
            Faq::TITLE => 'What does Watch Event do?',
            Faq::DESCRIPTION => 'Our own answer.',
        ]);

        $this->migration()->up();

        $answers = Faq::where(Faq::TITLE, 'What does Watch Event do?')->pluck(Faq::DESCRIPTION);
        $this->assertSame(['Our own answer.'], $answers->all());
    }

    public function test_up_leaves_a_faq_the_seeder_did_not_write(): void
    {
        $section = FaqSection::factory()->create([FaqSection::LABEL => 'Our Garrison']);
        Faq::factory()->create([Faq::SECTION_ID => $section->id]);

        $this->migration()->up();

        $this->assertSame(1, FaqSection::count());
        $this->assertSame(1, Faq::count());
    }

    public function test_up_puts_the_new_sections_before_the_videos(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);

        $this->migration()->up();

        $labels = FaqSection::orderBy(FaqSection::SORT_ORDER)->pluck(FaqSection::LABEL)->all();
        $this->assertSame(
            ['Service Records & Photos', 'Notifications & the Mobile App', 'Your Account', 'How-To Videos'],
            array_slice($labels, -4),
        );
    }

    private function item(string $title): Faq
    {
        return Faq::where(Faq::TITLE, $title)->firstOrFail();
    }

    /**
     * The FAQ as the page shows it: sections and their questions in order.
     *
     * @return list<array{string, string, list<array{string, ?string, ?string}>}>
     */
    private function snapshot(): array
    {
        return FaqSection::orderBy(FaqSection::SORT_ORDER)->orderBy(FaqSection::ID)->get()
            ->map(fn (FaqSection $section): array => [
                $section->label,
                $section->icon,
                Faq::where(Faq::SECTION_ID, $section->id)
                    ->orderBy(Faq::SORT_ORDER)
                    ->orderBy(Faq::ID)
                    ->get()
                    ->map(fn (Faq $faq): array => [$faq->title, $faq->description, $faq->video_url])
                    ->all(),
            ])
            ->all();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_25_000001_update_faq_answers.php');
    }
}
