<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Home\PageData;

use App\Messages\Home\PageData\HomePageData;
use App\Plugins\PluginLoginProviders;
use Tests\Fixtures\Auth\ForumLoginProvider;
use Tests\TestCase;

class HomePageDataTest extends TestCase
{
    protected function tearDown(): void
    {
        ForumLoginProvider::reset();

        parent::tearDown();
    }

    public function test_handle_sends_login_to_the_login_page(): void
    {
        $subject = new HomePageData;
        $result = $subject->handle(app(PluginLoginProviders::class));

        $this->assertSame([
            'login_url' => route('auth.login'),
            'login_label' => __('layout.Login'),
        ], $result);
    }

    public function test_handle_sends_login_straight_to_a_required_provider(): void
    {
        ForumLoginProvider::$required = true;
        $providers = app(PluginLoginProviders::class);
        $providers->add(ForumLoginProvider::class);

        $subject = new HomePageData;
        $result = $subject->handle($providers);

        $this->assertSame(route('auth.oauth-redirect', ['provider' => 'forum']), $result['login_url']);
        $this->assertSame('Login with Florida Garrison', $result['login_label']);
    }
}
