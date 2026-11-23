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
    public function test_handle_returns_only_what_the_sign_up_page_reads(): void
    {
        Mockery::mock('alias:'.GetAuthConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn([
                'session' => ['method' => 'email'],
                'providers' => [
                    ['key' => 'google', 'name' => 'Google', 'icon_url' => 'https://example.com/g.svg'],
                ],
                'required' => null,
                'email_password' => ['enabled' => true],
            ]);

        $subject = new SignUpPageData;

        $result = json_decode(json_encode($subject->handle()), true);

        $this->assertSame([
            'oauth' => [
                'providers' => [
                    ['key' => 'google', 'name' => 'Google', 'icon_url' => 'https://example.com/g.svg'],
                ],
                'required' => null,
            ],
        ], $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
