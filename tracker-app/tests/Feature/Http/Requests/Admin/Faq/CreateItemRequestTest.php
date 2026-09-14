<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Faq;

use App\Http\Requests\Admin\Faq\CreateItemRequest;
use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CreateItemRequestTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asAdministrator()->create();
        $this->actingAs($this->admin);
    }

    public function test_authorize_returns_true_for_admin(): void
    {
        $subject = new CreateItemRequest;
        $subject->setUserResolver(fn() => $this->admin);

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_admin(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $subject = new CreateItemRequest;
        $subject->setUserResolver(fn() => $member);

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_require_section_id(): void
    {
        $subject = new CreateItemRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Faq::SECTION_ID, $rules);
        $this->assertContains('required', $rules[Faq::SECTION_ID]);
    }

    public function test_rules_require_title(): void
    {
        $subject = new CreateItemRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Faq::TITLE, $rules);
        $this->assertContains('required', $rules[Faq::TITLE]);
    }

    public function test_rules_validate_section_id_exists(): void
    {
        $subject = new CreateItemRequest;
        $validator = Validator::make(
            [
                Faq::SECTION_ID => 999999,
                Faq::TITLE => 'Test FAQ',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Faq::SECTION_ID, $validator->errors()->toArray());
    }

    public function test_rules_require_description_or_video_url(): void
    {
        $subject = new CreateItemRequest;
        $validator = Validator::make(
            [
                Faq::SECTION_ID => 1,
                Faq::TITLE => 'Test FAQ',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Faq::DESCRIPTION, $validator->errors()->toArray());
        $this->assertArrayHasKey(Faq::VIDEO_URL, $validator->errors()->toArray());
    }

    public function test_rules_accept_valid_item_data_with_description_only(): void
    {
        $section = FaqSection::factory()->create();
        $subject = new CreateItemRequest;

        $validator = Validator::make(
            [
                Faq::SECTION_ID => $section->id,
                Faq::TITLE => 'How do I register?',
                Faq::DESCRIPTION => 'Detailed instructions',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accept_valid_item_data_with_video_url_only(): void
    {
        $section = FaqSection::factory()->create();
        $subject = new CreateItemRequest;

        $validator = Validator::make(
            [
                Faq::SECTION_ID => $section->id,
                Faq::TITLE => 'How do I register?',
                Faq::VIDEO_URL => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
