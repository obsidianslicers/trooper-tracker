<?php

declare(strict_types=1);

namespace App\Plugins;

use Closure;
use Hyperdrive\Events\MessageHandled;
use Hyperdrive\Events\MessageHandling;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Throwable;

/**
 * Base class for every plugin's service provider.
 *
 * Override registerPlugin() and bootPlugin() instead of register() and boot().
 * Before bootPlugin() runs, the plugin's conventional folders are loaded:
 *
 * - config/config.php        merged into config("plugins.<slug>")
 * - routes/web.php           under /plugins/<slug>, named "plugins.<slug>."
 * - resources/views          as the "<slug>::" view namespace
 * - database/migrations      included in "php artisan migrate"
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    public function __construct(Application $app, protected readonly PluginManifest $plugin)
    {
        parent::__construct($app);
    }

    final public function register(): void
    {
        $this->guard('register', function (): void
        {
            $this->mergeConfigIfPresent();
            $this->registerPlugin();
        });
    }

    final public function boot(): void
    {
        $this->guard('boot', function (): void
        {
            $this->loadConventionalResources();
            $this->app->call([$this, 'bootPlugin']);
        });
    }

    protected function registerPlugin(): void {}

    public function bootPlugin(): void {}

    /**
     * Read one of this plugin's admin-editable settings.
     */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->app->make(PluginManager::class)->setting($this->plugin->slug, $key, $default);
    }

    /**
     * Listen for an application event, such as App\Events\EventPosted.
     */
    protected function listen(string $event, Closure|string|array $listener): void
    {
        Event::listen($event, $listener);
    }

    /**
     * Run a callback before a Hyperdrive message of the given class is handled.
     *
     * @param  Closure(\Hyperdrive\Message): void  $callback
     */
    protected function beforeMessage(string $message_class, Closure $callback): void
    {
        Event::listen(
            MessageHandling::class,
            function (MessageHandling $event) use ($message_class, $callback): void
            {
                if ($event->message instanceof $message_class)
                {
                    $callback($event->message);
                }
            },
        );
    }

    /**
     * Run a callback after a Hyperdrive message of the given class is handled.
     *
     * @param  Closure(\Hyperdrive\Message, mixed): void  $callback
     */
    protected function afterMessage(string $message_class, Closure $callback): void
    {
        Event::listen(
            MessageHandled::class,
            function (MessageHandled $event) use ($message_class, $callback): void
            {
                if ($event->message instanceof $message_class)
                {
                    $callback($event->message, $event->result);
                }
            },
        );
    }

    /**
     * Add a view to a UI slot, such as "navbar.links" or "admin.dashboard.cards".
     *
     * @param  array<string, mixed>|Closure(array): array  $data
     * @param  Closure(array): bool|null  $when
     */
    protected function addToSlot(
        string $slot,
        string $view,
        array|Closure $data = [],
        ?Closure $when = null,
        int $order = 100,
    ): void {
        $this->app->make(PluginSlots::class)->add($slot, $view, $data, $when, $order);
    }

    /**
     * Add validation rules and a save callback to a core form, such as
     * PluginForms::ORGANIZATION_UPDATE. Render the inputs through the form's slot.
     *
     * @param  array<string, mixed>  $rules
     * @param  Closure(\Illuminate\Database\Eloquent\Model, array): void|null  $saved
     */
    protected function extendForm(string $form, array $rules = [], ?Closure $saved = null): void
    {
        $this->app->make(PluginForms::class)->add($form, $rules, $saved);
    }

    /**
     * Add a row to the Admin > System Check page. Return true to pass, false to warn,
     * or a SystemCheckResult for full control.
     *
     * @param  Closure(): (bool|\App\Services\SystemCheckResult)  $check
     */
    protected function addSystemCheck(string $label, Closure $check): void
    {
        $this->app->make(PluginSystemChecks::class)->add($label, $check);
    }

    /**
     * Count donations from another system toward troopers' donation totals and milestones.
     *
     * @param  class-string<\App\Contracts\DonationSource>  $source_class
     */
    protected function addDonationSource(string $source_class): void
    {
        $this->app->make(PluginDonations::class)->add($source_class);
    }

    /**
     * Offer a forum's user groups on the permission request page. One provider
     * per site; a later plugin replaces an earlier one.
     *
     * @param  class-string<\App\Contracts\ForumGroupProvider>  $provider_class
     */
    protected function setForumGroupProvider(string $provider_class): void
    {
        $this->app->make(PluginForumGroups::class)->set($provider_class);
    }

    /**
     * Run a middleware on every web request, after core's own web middleware.
     */
    protected function addWebMiddleware(string $middleware_class): void
    {
        $this->app['router']->pushMiddlewareToGroup('web', $middleware_class);
    }

    /**
     * Add scheduled tasks, such as an hourly sync. They run only while the plugin is enabled.
     *
     * @param  Closure(Schedule): void  $callback
     */
    protected function schedule(Closure $callback): void
    {
        $this->callAfterResolving(Schedule::class, $callback);
    }

    private function mergeConfigIfPresent(): void
    {
        $path = $this->plugin->path.'/config/config.php';

        if (is_file($path))
        {
            $this->mergeConfigFrom($path, "plugins.{$this->plugin->slug}");
        }
    }

    private function loadConventionalResources(): void
    {
        $slug = $this->plugin->slug;
        $path = $this->plugin->path;

        if (is_dir($path.'/resources/views'))
        {
            $this->loadViewsFrom($path.'/resources/views', $slug);
        }

        if (is_dir($path.'/database/migrations'))
        {
            $this->loadMigrationsFrom($path.'/database/migrations');
        }

        if (is_file($path.'/routes/web.php') && !$this->app->routesAreCached())
        {
            Route::middleware('web')
                ->prefix("plugins/{$slug}")
                ->name("plugins.{$slug}.")
                ->group($path.'/routes/web.php');
        }
    }

    private function guard(string $stage, Closure $callback): void
    {
        try
        {
            $callback();
        }
        catch (Throwable $e)
        {
            Log::error("Plugin [{$this->plugin->slug}] failed to {$stage}: {$e->getMessage()}", [
                'exception' => $e,
            ]);

            report($e);
        }
    }
}
