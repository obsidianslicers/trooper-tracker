<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Queries;

use App\Messages\Faq\Queries\GetFaqSections;
use App\Models\FaqSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetFaqSectionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_sections_in_sort_order(): void
    {
        $last_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 3,
        ]);
        $first_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);
        $middle_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 2,
        ]);

        $result = (new GetFaqSections())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(
            [$first_section->id, $middle_section->id, $last_section->id],
            $result->modelKeys(),
        );
    }

    public function test_handle_uses_id_as_a_tie_breaker_for_sort_order(): void
    {
        $first_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);
        $second_section = FaqSection::factory()->create([
            FaqSection::SORT_ORDER => 1,
        ]);

        $result = (new GetFaqSections())->handle();

        $this->assertSame(
            [$first_section->id, $second_section->id],
            $result->modelKeys(),
        );
    }

    public function test_handle_returns_empty_collection_when_no_sections_exist(): void
    {
        $result = (new GetFaqSections())->handle();

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(0, $result);
    }
}

