<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\PageData;

use App\Messages\Auth\PageData\SignUpPageData;
use App\Messages\Auth\Queries\GetAuthConfig;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class SignUpPageDataTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_handle_returns_oauth_data_from_get_auth_config(): void
    {
        $oauth = [
            'session' => ['method' => 'email'],
            'providers' => [
                ['key' => 'google', 'name' => 'Google', 'icon_url' => 'https://example.com/g.svg'],
            ],
            'required' => null,
            'email_password' => [
                'enabled' => true,
            ],
        ];

        Mockery::mock('alias:'.GetAuthConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn($oauth);

        $subject = new SignUpPageData;

        $result = $subject->handle();

        $this->assertSame([
            'oauth' => $oauth,
        ], $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
