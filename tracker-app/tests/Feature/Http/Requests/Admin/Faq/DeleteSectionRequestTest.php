<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Faq;

use App\Http\Requests\Admin\Faq\DeleteSectionRequest;
use App\Models\Faq;
use App\Models\FaqSection;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DeleteSectionRequestTest extends TestCase
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
        $subject = new DeleteSectionRequest;
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
        $subject = new DeleteSectionRequest;
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

    public function test_authorize_throws_when_section_missing(): void
    {
        $subject = new DeleteSectionRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $subject->setRouteResolver(fn() => new class
        {
            public function parameter(string $key, mixed $default = null): mixed
                {
                    return $default;
                }
            });

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('FAQ section not found or unauthorized.');

        $subject->authorize();
    }

    public function test_rules_add_error_when_section_has_faqs(): void
    {
        $section = FaqSection::factory()->create();
        Faq::factory()->withSection($section)->count(2)->create();

        $subject = new DeleteSectionRequest;
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

        $rules = $subject->rules();
        $validator = Validator::make([], []);

        $rules[0]($validator);

        $this->assertTrue($validator->errors()->has('section'));
        $this->assertContains(
            'An FAQ section with FAQs cannot be deleted.',
            $validator->errors()->get('section')
        );
    }

    public function test_rules_accept_empty_section_for_deletion(): void
    {
        $section = FaqSection::factory()->create();

        $subject = new DeleteSectionRequest;
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

        $rules = $subject->rules();
        $validator = Validator::make([], []);

        $rules[0]($validator);

        $this->assertFalse($validator->errors()->has('section'));
    }
}

