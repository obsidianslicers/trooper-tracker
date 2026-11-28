<?php

declare(strict_types=1);

namespace Tests\Feature\Database;

use App\Models\Faq;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Database\Fixtures\FaqBefore20261125Seeder;
use Tests\TestCase;

class PointFaqToTroopCreditRequestsTest extends TestCase
{
    use RefreshDatabase;

    private const TITLE = 'I am missing troop data / My troop data is incorrect.';

    public function test_up_points_the_seeded_answer_to_request_troop_credit(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);

        $this->migration()->up();

        $this->assertStringContainsString('Request Troop Credit', $this->answer());
    }

    public function test_up_keeps_an_answer_command_staff_edited(): void
    {
        $this->seed(FaqBefore20261125Seeder::class);
        Faq::where(Faq::TITLE, self::TITLE)->update([Faq::DESCRIPTION => 'Email the membership officer.']);

        $this->migration()->up();

        $this->assertSame('Email the membership officer.', $this->answer());
    }

    private function answer(): string
    {
        return (string) Faq::where(Faq::TITLE, self::TITLE)->value(Faq::DESCRIPTION);
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_27_000002_point_faq_to_troop_credit_requests.php');
    }
}
