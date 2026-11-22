<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Plugins\PageData;

use App\Messages\Plugins\PageData\UpdatePluginSettingsPageData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\Feature\Plugins\UsesFixturePlugins;
use Tests\TestCase;

class UpdatePluginSettingsPageDataTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    public function test_handle_fills_the_form_but_never_sends_a_saved_secret(): void
    {
        $plugins = $this->useFixturePlugins();
        $plugins->updateSettings('hello', ['greeting' => 'Howdy', 'api_key' => 'super-secret', 'is_loud' => true]);

        $result = (new UpdatePluginSettingsPageData('hello'))->handle($plugins);
        $fields = collect($result['settings'])->keyBy('key');

        $this->assertSame(['slug' => 'hello', 'name' => 'Hello'], $result['plugin']);
        $this->assertSame('Howdy', $fields['greeting']['value']);
        $this->assertSame('', $fields['api_key']['value']);
        $this->assertTrue($fields['api_key']['is_saved']);
        $this->assertSame('1', $fields['is_loud']['value']);
        $this->assertStringNotContainsString('super-secret', json_encode($result));
    }

    public function test_handle_uses_the_defaults_before_anything_is_saved(): void
    {
        $plugins = $this->useFixturePlugins();

        $fields = collect((new UpdatePluginSettingsPageData('hello'))->handle($plugins)['settings'])->keyBy('key');

        $this->assertSame('Hello', $fields['greeting']['value']);
        $this->assertFalse($fields['api_key']['is_saved']);
        $this->assertSame('0', $fields['is_loud']['value']);
    }

    public function test_handle_is_not_found_for_an_unknown_plugin(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new UpdatePluginSettingsPageData('nope'))->handle($this->useFixturePlugins());
    }
}
