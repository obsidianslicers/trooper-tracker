<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Faq;

use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateItemSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_faq_item_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $section = FaqSection::factory()->create();
        $item = Faq::factory()->create();

        $response = $this->actingAs($trooper)->post(
            route('admin.faq.items.update', ['item' => $item->id]),
            [
                Faq::SECTION_ID => $section->id,
                Faq::TITLE => 'Updated question',
                Faq::DESCRIPTION => 'Updated answer',
                Faq::VIDEO_URL => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('tt_faq', [
            Faq::ID => $item->id,
            Faq::SECTION_ID => $section->id,
            Faq::TITLE => 'Updated question',
            Faq::DESCRIPTION => 'Updated answer',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $item = Faq::factory()->create();

        $response = $this->post(route('admin.faq.items.update', ['item' => $item->id]), [
            Faq::SECTION_ID => FaqSection::factory()->create()->id,
            Faq::TITLE => 'Updated question',
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
