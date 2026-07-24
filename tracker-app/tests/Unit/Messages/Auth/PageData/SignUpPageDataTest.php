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
            'xenforo' => [
                'name' => 'Florida Garrison',
                'required' => false,
                'configured' => true,
            ],
            'google' => [
                'enabled' => true,
                'configured' => true,
            ],
            'email_password' => [
                'enabled' => true,
            ],
        ];

        Mockery::mock('alias:' . GetAuthConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn($oauth);

        $subject = new SignUpPageData();

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