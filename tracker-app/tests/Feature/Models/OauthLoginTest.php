<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\OauthLogin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OauthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_oauth_login(): void
    {
        $subject = OauthLogin::factory()->create();

        $this->assertInstanceOf(OauthLogin::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}