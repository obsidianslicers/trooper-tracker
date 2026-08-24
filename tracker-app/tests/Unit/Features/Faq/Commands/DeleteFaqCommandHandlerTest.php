<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Faq\Commands;

use App\Features\Faq\Commands\DeleteFaqCommand;
use App\Features\Faq\Commands\DeleteFaqCommandHandler;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteFaqCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    private DeleteFaqCommandHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new DeleteFaqCommandHandler;
    }

    public function test_invoke_soft_deletes_faq(): void
    {
        $faq = Faq::factory()->create();

        ($this->subject)(new DeleteFaqCommand($faq));

        $this->assertSoftDeleted('tt_faq', [Faq::ID => $faq->id]);
    }
}
