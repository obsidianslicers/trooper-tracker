<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Faq;

use App\Http\Requests\Admin\Faq\UpdateItemRequest;
use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateItemRequestTest extends TestCase
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
        $faq = Faq::factory()->create();
        $subject = new UpdateItemRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $subject->setRouteResolver(fn() => new class ($faq)
        {
            public function __construct(private readonly Faq $faq)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'item' ? $this->faq : $default;
                }
            });

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_admin(): void
    {
        $faq = Faq::factory()->create();
        $member = Trooper::factory()->asMember()->create();
        $subject = new UpdateItemRequest;
        $subject->setUserResolver(fn() => $member);
        $subject->setRouteResolver(fn() => new class ($faq)
        {
            public function __construct(private readonly Faq $faq)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'item' ? $this->faq : $default;
                }
            });

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_require_section_id(): void
    {
        $subject = new UpdateItemRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Faq::SECTION_ID, $rules);
        $this->assertContains('required', $rules[Faq::SECTION_ID]);
    }

    public function test_rules_require_title(): void
    {
        $subject = new UpdateItemRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(Faq::TITLE, $rules);
        $this->assertContains('required', $rules[Faq::TITLE]);
    }

    public function test_rules_validate_section_id_exists(): void
    {
        $subject = new UpdateItemRequest;
        $validator = Validator::make(
            [
                Faq::SECTION_ID => 999999,
                Faq::TITLE => 'Updated FAQ',
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(Faq::SECTION_ID, $validator->errors()->toArray());
    }

    public function test_rules_require_description_or_video_url(): void
    {
        $subject = new UpdateItemRequest;
        $validator = Validator::make(
            [
                Faq::SECTION_ID => 1,
                Faq::TITLE => 'Updated FAQ',
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
        $subject = new UpdateItemRequest;

        $validator = Validator::make(
            [
                Faq::SECTION_ID => $section->id,
                Faq::TITLE => 'Updated question',
                Faq::DESCRIPTION => 'Updated answer',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }

    public function test_rules_accept_valid_item_data_with_video_url_only(): void
    {
        $section = FaqSection::factory()->create();
        $subject = new UpdateItemRequest;

        $validator = Validator::make(
            [
                Faq::SECTION_ID => $section->id,
                Faq::TITLE => 'Updated question',
                Faq::VIDEO_URL => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
