<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Home\Pages;

use Tests\TestCase;

class ShowStatusControllerTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = storage_path('app/deploy.json');
        @unlink($this->path);
    }

    protected function tearDown(): void
    {
        @unlink($this->path);
        parent::tearDown();
    }

    public function test_invoke_returns_nulls_without_a_deploy_file(): void
    {
        $this->get(route('status'))
            ->assertOk()
            ->assertExactJson(['commit' => null, 'deployed_at' => null]);
    }

    public function test_invoke_returns_commit_and_deploy_time(): void
    {
        file_put_contents($this->path, json_encode([
            'commit' => 'abc123def456',
            'deployed_at' => '2026-11-26T14:30:00+00:00',
        ]));

        $this->get(route('status'))
            ->assertOk()
            ->assertExactJson(['commit' => 'abc123def456', 'deployed_at' => '2026-11-26T14:30:00+00:00']);
    }
}
