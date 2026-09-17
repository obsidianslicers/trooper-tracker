<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Requests\Admin\Faq;

use App\Http\Requests\Admin\Faq\DeleteItemRequest;
use App\Models\Faq;
use App\Models\Trooper;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteItemRequestTest extends TestCase
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
        $subject = new DeleteItemRequest;
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
        $subject = new DeleteItemRequest;
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

    public function test_authorize_throws_when_item_missing(): void
    {
        $subject = new DeleteItemRequest;
        $subject->setUserResolver(fn() => $this->admin);
        $subject->setRouteResolver(fn() => new class
        {
            public function parameter(string $key, mixed $default = null): mixed
                {
                    return $default;
                }
            });

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('FAQ not found or unauthorized.');

        $subject->authorize();
    }

    public function test_rules_are_empty(): void
    {
        $subject = new DeleteItemRequest;

        $this->assertSame([], $subject->rules());
    }
}
