<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Faq;

use App\Http\Requests\Admin\Faq\UpdateSectionRequest;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateSectionRequestTest extends TestCase
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
        $section = FaqSection::factory()->create();
        $subject = new UpdateSectionRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $subject->setRouteResolver(fn() => new class ($section)
        {
            public function __construct(private readonly FaqSection $section)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'section' ? $this->section : $default;
                }
            });

        $this->assertTrue($subject->authorize());
    }

    public function test_authorize_returns_false_for_non_admin(): void
    {
        $section = FaqSection::factory()->create();
        $member = Trooper::factory()->asMember()->create();
        $subject = new UpdateSectionRequest;
        $subject->setUserResolver(fn() => $member);
        $subject->setRouteResolver(fn() => new class ($section)
        {
            public function __construct(private readonly FaqSection $section)
                {
                }

                public function parameter(string $key, mixed $default = null): mixed
                {
                    return $key === 'section' ? $this->section : $default;
                }
            });

        $this->assertFalse($subject->authorize());
    }

    public function test_rules_require_label(): void
    {
        $subject = new UpdateSectionRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(FaqSection::LABEL, $rules);
        $this->assertContains('required', $rules[FaqSection::LABEL]);
    }

    public function test_rules_require_icon(): void
    {
        $subject = new UpdateSectionRequest;
        $rules = $subject->rules();

        $this->assertArrayHasKey(FaqSection::ICON, $rules);
        $this->assertContains('required', $rules[FaqSection::ICON]);
    }

    public function test_rules_validate_icon_max_length(): void
    {
        $subject = new UpdateSectionRequest;

        $validator = Validator::make(
            [
                FaqSection::LABEL => 'General',
                FaqSection::ICON => str_repeat('a', 65),
            ],
            $subject->rules()
        );

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey(FaqSection::ICON, $validator->errors()->toArray());
    }

    public function test_rules_accept_valid_section_data(): void
    {
        $subject = new UpdateSectionRequest;

        $validator = Validator::make(
            [
                FaqSection::LABEL => 'General',
                FaqSection::ICON => 'question-circle',
            ],
            $subject->rules()
        );

        $this->assertFalse($validator->fails());
    }
}
