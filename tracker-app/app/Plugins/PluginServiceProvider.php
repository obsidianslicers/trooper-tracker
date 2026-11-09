<?php

declare(strict_types=1);

namespace App\Plugins;

use App\Enums\PluginCapability;
use App\Http\Middleware\CheckVisitorAccessMiddleware;
use App\Http\Middleware\IsolatePluginMiddleware;
use App\Http\Middleware\TrooperSetupRequiredMiddleware;
use Closure;
use Hyperdrive\Events\MessageHandled;
use Hyperdrive\Events\MessageHandling;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
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
 * - routes/web.php           /plugins/<slug>, "plugins.<slug>.", signed-in active troopers
 * - routes/admin.php         /plugins/<slug>/admin, "plugins.<slug>.admin.", administrators
 * - routes/public.php        /plugins/<slug>, "plugins.<slug>.", anyone
 * - resources/views          as the "<slug>::" view namespace
 * - database/migrations      included in "php artisan migrate"
 */
abstract class PluginServiceProvider extends ServiceProvider
{
    public const PUBLIC_MIDDLEWARE = ['web'];

    public const TROOPER_MIDDLEWARE = ['web', 'auth', 'verified', 'check.active'];

    public const ADMINISTRATOR_MIDDLEWARE = [
        'web',
        'auth',
        'verified',
        'check.active',
        'check.role:staff_officer,system_owner',
    ];

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
     * Runs each time the plugin is turned on, after its migrations and boot.
     */
    public function pluginEnabled(): void {}

    /**
     * Read one of this plugin's admin-editable settings.
     */
    protected function setting(string $key, mixed $default = null): mixed
    {
        return $this->app->make(PluginManager::class)->setting($this->plugin->slug, $key, $default);
    }

    /**
     * Listen for an application event, such as App\Events\EventPosted. If the
     * listener throws, the error is logged and the event carries on without it.
     * A queued listener (ShouldQueue) is only queued here; the queue retries it.
     */
    protected function listen(string $event, Closure|string|array $listener): void
    {
        $this->requireCapability(PluginCapability::EVENTS);
        $is_wildcard = str_contains($event, '*');
        $handler = $this->app['events']->makeListener($listener, $is_wildcard);
        $what = "handle [{$event}] in plugin [{$this->plugin->slug}]";

        Event::listen($event, fn (mixed ...$args): mixed => PluginGuard::attempt(
            $what,
            fn (): mixed => $is_wildcard ? $handler(...$args) : $handler($event, $args),
        ));
    }

    /**
     * Run a callback before a Hyperdrive message of the given class is handled.
     *
     * @param  Closure(\Hyperdrive\Message): void  $callback
     */
    protected function beforeMessage(string $message_class, Closure $callback): void
    {
        $this->requireCapability(PluginCapability::EVENTS);
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
        $this->requireCapability(PluginCapability::EVENTS);
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
        $this->requireCapability(PluginCapability::FORMS);
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
        $this->requireCapability(PluginCapability::DONATIONS);
        $this->app->make(PluginDonations::class)->add($source_class);
    }

    /**
     * Give the club synchronizers somewhere to read roster spreadsheets from.
     *
     * @param  class-string<\App\Contracts\SheetSource>  $source_class
     */
    protected function setSheetSource(string $source_class): void
    {
        $this->requireCapability(PluginCapability::CLUB_DATA);
        $this->app->make(PluginSheetSource::class)->set($source_class);
    }

    /**
     * Offer a way to keep a club's roster and costumes in step with the club's own
     * records. Admins pick it per club on the organization page.
     *
     * @param  class-string  $synchronizer_class  implements SynchronizerInterface,
     *                                            MemberLookupSource, or both
     */
    protected function addClubSynchronizer(
        string $key,
        string $label,
        string $synchronizer_class,
    ): void {
        $this->requireCapability(PluginCapability::CLUB_DATA);
        $this->app->make(PluginClubSynchronizers::class)->add($key, $label, $synchronizer_class);
    }

    /**
     * Let permission requests ask for something more, such as forum user groups.
     *
     * @param  class-string<\App\Contracts\PermissionRequestExtension>  $extension_class
     */
    protected function addPermissionRequestExtension(string $extension_class): void
    {
        $this->requireCapability(PluginCapability::FORMS);
        $this->app->make(PluginPermissionRequests::class)->add($extension_class);
    }

    /**
     * Let troopers sign in through another site. Register a Socialite driver
     * named after the provider's key() as well.
     *
     * @param  class-string<\App\Contracts\LoginProvider>  $provider_class
     */
    protected function addLoginProvider(string $provider_class): void
    {
        $this->requireCapability(PluginCapability::LOGIN);
        $this->app->make(PluginLoginProviders::class)->add($provider_class);
    }

    /**
     * Add a notification category troopers can turn on or off per channel on their
     * account page. Set it as a notification's $notification_category. Command staff
     * categories are shown to administrators and moderators only; administrator-only
     * ones to administrators only.
     */
    protected function addNotificationCategory(
        string $value,
        string $label,
        bool $for_command_staff = false,
        bool $administrator_only = false,
    ): void {
        $this->app->make(PluginNotifications::class)
            ->add($value, $label, $for_command_staff, $administrator_only);
    }

    /**
     * Run a middleware on every web request, after core's own web middleware.
     */
    protected function addWebMiddleware(string $middleware_class): void
    {
        $this->requireCapability(PluginCapability::MIDDLEWARE);
        $this->app['router']->pushMiddlewareToGroup(
            'web',
            IsolatePluginMiddleware::class.':'.$middleware_class,
        );
    }

    /**
     * Add scheduled tasks, such as an hourly sync. They run only while the plugin is enabled.
     *
     * @param  Closure(Schedule): void  $callback
     */
    protected function schedule(Closure $callback): void
    {
        $this->requireCapability(PluginCapability::SCHEDULE);
        $this->callAfterResolving(Schedule::class, $callback);
    }

    /**
     * Receive webhooks at POST /webhooks/<path>, named "webhooks.<name>". Only
     * this route skips the CSRF check and trooper setup redirects, so the
     * action must check the sender itself (a shared secret or a signature).
     */
    protected function addWebhook(string $path, Closure|string|array $action, string $name): void
    {
        $this->requireCapability(PluginCapability::WEBHOOKS);

        if ($this->app->routesAreCached())
        {
            return;
        }

        Route::middleware('web')
            ->post('/webhooks/'.ltrim($path, '/'), $action)
            ->withoutMiddleware([
                ValidateCsrfToken::class,
                TrooperSetupRequiredMiddleware::class,
                CheckVisitorAccessMiddleware::class,
            ])
            ->name("webhooks.{$name}");
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

        if (!$this->app->routesAreCached())
        {
            $this->loadConventionalRoutes();
        }
    }

    /**
     * Plugin pages need a signed-in, active trooper unless the plugin puts them
     * in routes/public.php on purpose. routes/admin.php is for administrators.
     */
    private function loadConventionalRoutes(): void
    {
        $slug = $this->plugin->slug;
        $files = [
            'public.php' => [self::PUBLIC_MIDDLEWARE, '', ''],
            'web.php' => [self::TROOPER_MIDDLEWARE, '', ''],
            'admin.php' => [self::ADMINISTRATOR_MIDDLEWARE, '/admin', 'admin.'],
        ];

        foreach ($files as $file => [$middleware, $prefix, $name])
        {
            $route_file = "{$this->plugin->path}/routes/{$file}";

            if (is_file($route_file))
            {
                Route::middleware($middleware)
                    ->prefix("plugins/{$slug}{$prefix}")
                    ->name("plugins.{$slug}.{$name}")
                    ->group($route_file);
            }
        }
    }

    /**
     * @throws PluginCapabilityException
     */
    private function requireCapability(PluginCapability $capability): void
    {
        if (!$this->plugin->can($capability))
        {
            throw PluginCapabilityException::notDeclared($this->plugin, $capability);
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
