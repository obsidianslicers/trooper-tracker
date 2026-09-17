<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Faq\Commands;

use App\Messages\Faq\Commands\DeleteFaqItem;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteFaqItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_soft_deletes_faq(): void
    {
        $faq = Faq::factory()->create();

        (new DeleteFaqItem($faq))->handle();

        $this->assertSoftDeleted('tt_faq', [Faq::ID => $faq->id]);
    }
}
