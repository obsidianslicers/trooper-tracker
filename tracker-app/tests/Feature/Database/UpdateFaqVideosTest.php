<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Faq;
use App\Models\FaqSection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\Feature\Database\Fixtures\FaqBefore20261125Seeder;
use Tests\TestCase;

class UpdateFaqVideosTest extends TestCase
{
    use RefreshDatabase;

    public function test_up_links_the_seeded_videos_to_the_new_recordings(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);

        $this->migration()->up();

        $this->assertSame('https://youtu.be/fn0cDEYxhzM', $this->video('Getting Started & Registration'));
        $this->assertSame('https://youtu.be/mB-AChFaEbc', $this->video('Command Staff - Trooper Requests'));
    }

    public function test_up_adds_the_new_videos_after_the_others(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);

        $this->migration()->up();

        $titles = $this->section()->pluck(Faq::TITLE)->all();
        $this->assertCount(16, $titles);
        $this->assertSame('Command Staff - Trooper Requests', $titles[8]);
        $this->assertSame('Confirming Your Troop', $titles[9]);
        $this->assertSame('Command Staff - Roles & Permissions', $titles[15]);
        $this->assertSame('https://youtu.be/E43C3HLT4Eg', $this->video('Linking Trackers with the HoloNet'));
    }

    public function test_up_keeps_a_video_command_staff_changed(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);
        Faq::where(Faq::TITLE, 'Adding Your Costumes')->update([Faq::VIDEO_URL => 'https://youtu.be/ours']);

        $this->migration()->up();

        $this->assertSame('https://youtu.be/ours', $this->video('Adding Your Costumes'));
    }

    public function test_up_twice_changes_nothing_more(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);
        $this->migration()->up();
        $once = $this->section()->map(fn (Faq $faq): array => [$faq->title, $faq->video_url])->all();

        $this->migration()->up();

        $twice = $this->section()->map(fn (Faq $faq): array => [$faq->title, $faq->video_url])->all();
        $this->assertSame($once, $twice);
    }

    public function test_up_leaves_a_faq_without_the_videos_section(): void
    {
        $section = FaqSection::factory()->create([FaqSection::LABEL => 'Our Garrison']);
        Faq::factory()->create([Faq::SECTION_ID => $section->id]);

        $this->migration()->up();

        $this->assertSame(1, Faq::count());
    }

    private function video(string $title): ?string
    {
        return Faq::where(Faq::TITLE, $title)->value(Faq::VIDEO_URL);
    }

    /**
     * @return Collection<int, Faq>
     */
    private function section(): Collection
    {
        $section = FaqSection::where(FaqSection::LABEL, 'How-To Videos')->firstOrFail();

        return Faq::where(Faq::SECTION_ID, $section->id)
            ->orderBy(Faq::SORT_ORDER)
            ->orderBy(Faq::ID)
            ->get();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_28_000001_update_faq_videos.php');
    }
}
