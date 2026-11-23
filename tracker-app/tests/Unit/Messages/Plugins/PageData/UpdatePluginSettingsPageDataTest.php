<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Plugins\PageData;

use App\Messages\Plugins\PageData\UpdatePluginSettingsPageData;
use App\Plugins\PluginManager;
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

        $result = $this->payload($plugins);
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

        $fields = collect($this->payload($plugins)['settings'])->keyBy('key');

        $this->assertSame('Hello', $fields['greeting']['value']);
        $this->assertFalse($fields['api_key']['is_saved']);
        $this->assertSame('0', $fields['is_loud']['value']);
    }

    public function test_handle_is_not_found_for_an_unknown_plugin(): void
    {
        $this->expectException(NotFoundHttpException::class);

        (new UpdatePluginSettingsPageData('nope'))->handle($this->useFixturePlugins());
    }

    /**
     * What the settings page receives, with the Resources serialized.
     *
     * @return array<string, mixed>
     */
    private function payload(PluginManager $plugins): array
    {
        return json_decode(json_encode((new UpdatePluginSettingsPageData('hello'))->handle($plugins)), true);
    }
}
