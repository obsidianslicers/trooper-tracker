<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\PageData;

use App\Messages\Account\PageData\DeniedPageData;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeniedPageDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_required_keys(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $subject = new DeniedPageData($trooper);
        $result = $subject->handle();

        $this->assertArrayHasKey('denied_requests', $result);
        $this->assertArrayHasKey('denial_reason', $result);
        $this->assertArrayHasKey('organization_hierarchy', $result);
        $this->assertArrayHasKey('account_type', $result);
    }

    public function test_handle_includes_denied_requests(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $subject = new DeniedPageData($trooper);
        $result = $subject->handle();

        $this->assertIsIterable($result['denied_requests']);
    }
}
