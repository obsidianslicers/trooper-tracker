<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HasTrooperScopesTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('searchDataProvider')]
    public function test_search_for_scope(string $search_term, string $expected_field): void
    {
        // Arrange
        $matching_trooper = Trooper::factory()->create([
            'name' => 'John Match Doe',
            'username' => 'jmatchdoe',
            'email' => 'jmatchdoe@example.com',
        ]);

        Trooper::factory()->create([
            'name' => 'Jane NoMatch Smith',
            'username' => 'jnomatchsmith',
            'email' => 'jnomatchsmith@example.com',
        ]);

        // Act
        $result = Trooper::searchFor($search_term)->get();

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals($matching_trooper->id, $result->first()->id);
    }

    /**
     * Data provider for the searchFor scope test.
     *
     * @return array
     */
    public static function searchDataProvider(): array
    {
        return [
            'searches by name' => ['%John%', 'name'],
            'searches by username' => ['%jmatch%', 'username'],
            'searches by email' => ['%jmatch%', 'email'],
        ];
    }
}
