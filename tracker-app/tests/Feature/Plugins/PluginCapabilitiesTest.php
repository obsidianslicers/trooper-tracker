<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins;

use App\Models\Trooper;
use App\Plugins\PluginManager;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use ReflectionProperty;
use Tests\TestCase;

class PluginCapabilitiesTest extends TestCase
{
    use RefreshDatabase;
    use UsesFixturePlugins;

    private string $plugins_path;

    private PluginManager $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->plugins_path = sys_get_temp_dir().'/tt-plugin-capabilities-'.uniqid();
        $this->subject = $this->useFixturePlugins($this->plugins_path);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->plugins_path);

        parent::tearDown();
    }

    public function test_a_hook_the_plugin_did_not_declare_stops_it_booting(): void
    {
        Log::spy();
        $this->writePlugin('sneaky', [], '$this->addWebMiddleware(\\stdClass::class);');

        $this->subject->load($this->subject->findOrFail('sneaky'));

        Log::shouldHaveReceived('error')
            ->withArgs(fn (string $message): bool => str_contains(
                $message,
                'does not list "middleware" under "capabilities"',
            ))
            ->atLeast()->once();
        $this->assertNotContains(
            'App\Http\Middleware\IsolatePluginMiddleware:stdClass',
            $this->app['router']->getMiddlewareGroups()['web'],
        );
    }

    public function test_a_declared_hook_runs(): void
    {
        $this->writePlugin('honest', ['middleware'], '$this->addWebMiddleware(\\stdClass::class);');

        $this->subject->load($this->subject->findOrFail('honest'));

        $this->assertContains(
            'App\Http\Middleware\IsolatePluginMiddleware:stdClass',
            $this->app['router']->getMiddlewareGroups()['web'],
        );
    }

    public function test_a_webhook_skips_csrf_only_on_its_own_route(): void
    {
        $this->writePlugin(
            'hooks',
            ['webhooks'],
            '$this->addWebhook(\'hooks\', fn () => \'received\', \'hooks\');',
        );
        $this->subject->load($this->subject->findOrFail('hooks'));
        $this->app['router']->getRoutes()->refreshNameLookups();

        $route = $this->app['router']->getRoutes()->getByName('webhooks.hooks');

        $this->assertSame('webhooks/hooks', $route->uri());
        $this->assertContains(ValidateCsrfToken::class, $route->excludedMiddleware());
        $this->assertNotContains(
            'webhooks/*',
            (new ReflectionProperty(ValidateCsrfToken::class, 'neverVerify'))->getValue(),
        );
    }

    public function test_plugin_pages_need_a_signed_in_trooper_by_default(): void
    {
        $this->writePlugin('pages', routes: [
            'web.php' => "Route::get('/members', fn () => 'members');",
            'admin.php' => "Route::get('/', fn () => 'admins');",
            'public.php' => "Route::get('/open', fn () => 'anyone');",
        ]);
        $this->subject->load($this->subject->findOrFail('pages'));

        $this->get('/plugins/pages/open')->assertOk()->assertSee('anyone');
        $this->get('/plugins/pages/members')->assertRedirect(route('auth.login'));
        $this->get('/plugins/pages/admin')->assertRedirect(route('auth.login'));

        $member = Trooper::factory()->asActive()->create(['email_verified_at' => now()]);
        $this->actingAs($member)->get('/plugins/pages/members')->assertOk();
        $this->actingAs($member)->get('/plugins/pages/admin')->assertForbidden();

        $admin = Trooper::factory()->asAdministrator()->create(['email_verified_at' => now()]);
        $this->actingAs($admin)->get('/plugins/pages/admin')->assertOk()->assertSee('admins');
    }

    public function test_the_list_says_what_a_plugin_can_do_and_asks_before_enabling(): void
    {
        $this->writePlugin('honest', ['middleware', 'login']);
        $trooper = Trooper::factory()->asAdministrator()->create();

        $this->actingAs($trooper)->get(route('admin.plugins.list'))
            ->assertSee('It runs on every page request and adds a way to sign in.')
            ->assertSee('Turn on Honest? It runs on every page request', false);
    }

    /**
     * @param  array<int, string>  $capabilities
     * @param  array<string, string>  $routes  file name => route code
     */
    private function writePlugin(
        string $slug,
        array $capabilities = [],
        string $boot = '',
        array $routes = [],
    ): void {
        $name = ucfirst($slug);
        $namespace = 'Capabilities'.$name.'Plugin';
        $path = "{$this->plugins_path}/{$slug}";
        $files = new Filesystem;

        $files->ensureDirectoryExists($path.'/src');
        file_put_contents($path.'/plugin.json', (string) json_encode([
            'slug' => $slug,
            'name' => $name,
            'namespace' => $namespace,
            'provider' => "{$namespace}\\Provider",
            'capabilities' => $capabilities,
        ]));
        file_put_contents($path.'/src/Provider.php', "<?php\n\nnamespace {$namespace};\n\n"
            ."class Provider extends \\App\\Plugins\\PluginServiceProvider\n{\n"
            ."    public function bootPlugin(): void\n    {\n        {$boot}\n    }\n}\n");

        foreach ($routes as $file => $code)
        {
            $files->ensureDirectoryExists($path.'/routes');
            file_put_contents("{$path}/routes/{$file}", "<?php\n\n"
                ."use Illuminate\\Support\\Facades\\Route;\n\n{$code}\n");
        }

        $this->subject->forgetManifests();
    }
}
