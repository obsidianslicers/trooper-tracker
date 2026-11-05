<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo;

use App\Events\EventClosed;
use App\Events\EventPosted;
use App\Events\EventWatchChanging;
use App\Http\Middleware\CheckVisitorAccessMiddleware;
use App\Http\Middleware\TrooperSetupRequiredMiddleware;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Plugins\PluginForms;
use App\Plugins\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Laravel\Socialite\Facades\Socialite;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;
use TroopTracker\Plugins\Xenforo\Console\SyncUserCommand;
use TroopTracker\Plugins\Xenforo\Console\SyncUsersCommand;
use TroopTracker\Plugins\Xenforo\Donations\XenforoDonationSource;
use TroopTracker\Plugins\Xenforo\Donations\XenforoProfile;
use TroopTracker\Plugins\Xenforo\ForumGroups\XenforoForumGroups;
use TroopTracker\Plugins\Xenforo\Http\Controllers\AccountLinkController;
use TroopTracker\Plugins\Xenforo\Http\Controllers\AccountLinkRequiredController;
use TroopTracker\Plugins\Xenforo\Http\Controllers\ForumReplyController;
use TroopTracker\Plugins\Xenforo\Http\Controllers\ShowForumAttachmentController;
use TroopTracker\Plugins\Xenforo\Http\Controllers\WebhookController;
use TroopTracker\Plugins\Xenforo\Http\Middleware\CheckXenforoBan;
use TroopTracker\Plugins\Xenforo\Http\Middleware\EnsureXenforoLinked;
use TroopTracker\Plugins\Xenforo\Listeners\ArchiveEventThread;
use TroopTracker\Plugins\Xenforo\Listeners\CreateEventThread;
use TroopTracker\Plugins\Xenforo\Listeners\SyncThreadWatch;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;
use TroopTracker\Plugins\Xenforo\Notifications\EventForumPostNotification;
use TroopTracker\Plugins\Xenforo\Notifications\ForumPostCommandStaffNotification;
use TroopTracker\Plugins\Xenforo\Observers\EventThreadObserver;
use TroopTracker\Plugins\Xenforo\Observers\EventTrooperThreadObserver;
use TroopTracker\Plugins\Xenforo\Socialite\XenforoProvider;
use TroopTracker\Plugins\Xenforo\Threads\EventComments;

class XenforoServiceProvider extends PluginServiceProvider
{
    /**
     * Settings saved on the plugin's page win over the matching .env values.
     */
    private const SETTING_CONFIG = [
        'base_url' => 'plugins.xenforo.base_url',
        'name' => 'plugins.xenforo.name',
        'api_key' => 'plugins.xenforo.api_key',
        'api_user' => 'plugins.xenforo.api_user',
        'client_id' => 'plugins.xenforo.client_id',
        'client_secret' => 'plugins.xenforo.client_secret',
        'redirect' => 'plugins.xenforo.redirect',
        'webhook_secret' => 'plugins.xenforo.webhook_secret',
        'require_xenforo' => 'plugins.xenforo.require_xenforo',
    ];

    protected function registerPlugin(): void
    {
        $this->applySettingsToConfig();

        // Per request, so the service record's slots and totals share one lookup.
        $this->app->scoped(XenforoProfile::class);
        $this->app->scoped(EventComments::class);
    }

    public function bootPlugin(): void
    {
        $this->commands([SyncUserCommand::class, SyncUsersCommand::class]);

        Socialite::extend('xenforo', fn (): XenforoProvider => Socialite::buildProvider(
            XenforoProvider::class,
            config('plugins.xenforo'),
        ));

        $this->addLoginProvider(XenforoLoginProvider::class);
        $this->addToSlot('navbar.member-links', 'xenforo::navbar.forum-link');
        $this->addWebMiddleware(EnsureXenforoLinked::class);
        $this->addWebMiddleware(CheckXenforoBan::class);
        $this->registerAccountRoutes();
        $this->registerServiceRecord();
        $this->registerEventThreads();
        $this->registerOrganizationFields();
        $this->setForumGroupProvider(XenforoForumGroups::class);

        $this->schedule(function (Schedule $schedule): void
        {
            $schedule->command(SyncUsersCommand::class)->hourly();
        });

        $this->addSystemCheck(
            'XenForo API',
            fn (): bool => filled(config('plugins.xenforo.base_url'))
                && filled(config('plugins.xenforo.api_key')),
        );
    }

    private function registerServiceRecord(): void
    {
        $this->addDonationSource(XenforoDonationSource::class);
        $this->addToSlot('service-records.trooper.badges', 'xenforo::service-records.banners');
        $this->addToSlot('service-records.trooper.actions', 'xenforo::service-records.profile-link');
        $this->addToSlot('service-records.trooper.donations', 'xenforo::service-records.donations');
    }

    private function registerEventThreads(): void
    {
        Event::observe(EventThreadObserver::class);
        EventTrooper::observe(EventTrooperThreadObserver::class);

        $this->addNotificationCategory(EventForumPostNotification::CATEGORY, 'Event Forum Post');
        $this->addNotificationCategory(
            ForumPostCommandStaffNotification::CATEGORY,
            'Forum Post Command Staff',
            for_command_staff: true,
        );

        $this->listen(EventPosted::class, CreateEventThread::class);
        $this->listen(EventClosed::class, ArchiveEventThread::class);
        $this->listen(EventWatchChanging::class, SyncThreadWatch::class);

        $this->addToSlot('events.display.discussion', 'xenforo::events.comments');
        $this->addToSlot('events.display.share-buttons', 'xenforo::events.forum-post-button');
        $this->addToSlot('admin.events.form.fields', 'xenforo::admin.event-fields');

        $this->extendForm(PluginForms::EVENT_UPDATE,
            rules: [
                'xenforo_create_thread' => ['sometimes', 'boolean'],
                'xenforo_thread_id' => ['nullable', 'integer', 'min:1'],
                'xenforo_post_id' => ['nullable', 'integer', 'min:1'],
            ],
            saved: fn (Event $event, array $validated) => $this->saveEventThread($event, $validated),
        );

        View::composer(
            ['pages.admin.events.inc.miscellaneous', 'pages.events.event-display'],
            fn ($view) => $view->with('smilies', app(EventComments::class)->smilies()),
        );

        $this->registerEventRoutes();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function saveEventThread(Event $event, array $validated): void
    {
        if (!array_key_exists('xenforo_create_thread', $validated))
        {
            return;
        }

        EventThread::store($event->id, [
            EventThread::SHOULD_CREATE_THREAD => (bool) $validated['xenforo_create_thread'],
            EventThread::THREAD_ID => $validated['xenforo_thread_id'] ?? null,
            EventThread::POST_ID => $validated['xenforo_post_id'] ?? null,
        ]);
    }

    /**
     * Kept at their old addresses and names.
     */
    private function registerEventRoutes(): void
    {
        Route::middleware(['web', 'auth', 'verified', 'check.active'])
            ->prefix('events')
            ->name('events.')
            ->group(function (): void
            {
                Route::post('/forum-reply/{event}', ForumReplyController::class)->name('forum-reply-htmx');
                Route::get('/forum-attachments/{attachment_id}', ShowForumAttachmentController::class)
                    ->whereNumber('attachment_id')
                    ->middleware('signed')
                    ->name('forum-attachment');
            });

        Route::middleware('web')
            ->post('/webhooks/xenforo', WebhookController::class)
            ->withoutMiddleware([
                TrooperSetupRequiredMiddleware::class,
                CheckVisitorAccessMiddleware::class,
            ])
            ->name('webhooks.xenforo');
    }

    private function registerOrganizationFields(): void
    {
        $this->addToSlot(
            'admin.organizations.update.fields',
            'xenforo::admin.organization-fields',
            fn (array $context): array => [
                'xenforo_forum' => OrganizationForum::forOrganization($context['organization']->id),
            ],
        );

        $fields = [];

        foreach (OrganizationForum::SETTINGS as $setting)
        {
            $fields['xenforo_'.$setting] = ['nullable', 'integer', 'min:1'];
        }

        $this->extendForm(PluginForms::ORGANIZATION_UPDATE,
            rules: $fields,
            saved: function (Organization $organization, array $validated): void
            {
                $values = [];

                foreach (OrganizationForum::SETTINGS as $setting)
                {
                    if (array_key_exists('xenforo_'.$setting, $validated))
                    {
                        $values[$setting] = $validated['xenforo_'.$setting];
                    }
                }

                if ($values !== [])
                {
                    OrganizationForum::store($organization->id, $values);
                }
            },
        );
    }

    /**
     * Kept at their old /account/xenforo addresses and account.xenforo.* names.
     * Troopers who haven't finished setup can still reach them.
     */
    private function registerAccountRoutes(): void
    {
        Route::middleware(['web', 'auth', 'redirect.denied', 'redirect.pending'])
            ->prefix('account/xenforo')
            ->name('account.xenforo.')
            ->group(function (): void
            {
                Route::get('/', AccountLinkController::class)
                    ->defaults(TrooperSetupRequiredMiddleware::ALLOWED, true)
                    ->name('index');
                Route::get('/required', AccountLinkRequiredController::class)
                    ->defaults(TrooperSetupRequiredMiddleware::ALLOWED, true)
                    ->name('required');
            });
    }

    private function applySettingsToConfig(): void
    {
        foreach (self::SETTING_CONFIG as $setting => $config_key)
        {
            $value = $this->setting($setting);

            if ($value !== null && $value !== '')
            {
                config([$config_key => $setting === 'base_url' ? rtrim($value, '/') : $value]);
            }
        }
    }
}
