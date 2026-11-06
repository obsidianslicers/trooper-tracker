<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet;

use App\Plugins\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use TroopTracker\Plugins\Holonet\Console\RefreshLinksCommand;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Http\Controllers\ExchangeLinkCodeController;
use TroopTracker\Plugins\Holonet\Http\Controllers\ForgetAccessKeyController;
use TroopTracker\Plugins\Holonet\Http\Controllers\ShareTroopHistoryController;
use TroopTracker\Plugins\Holonet\Support\LinkedTroopsOnPage;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;
use TroopTracker\Plugins\Holonet\Support\TrackerClient;

class HolonetServiceProvider extends PluginServiceProvider
{
    protected function registerPlugin(): void
    {
        $this->app->singleton(ThisTracker::class);
        $this->app->singleton(TrackerDirectory::class);
        $this->app->singleton(TrackerClient::class);
        $this->app->scoped(LinkedTroopsOnPage::class);
    }

    public function bootPlugin(): void
    {
        $this->commands([RefreshLinksCommand::class]);

        $this->addWebhook('holonet/token', ExchangeLinkCodeController::class, 'holonet.token');
        $this->addWebhook('holonet/history', ShareTroopHistoryController::class, 'holonet.history');
        $this->addWebhook('holonet/revoke', ForgetAccessKeyController::class, 'holonet.revoke');

        $this->schedule(function (Schedule $schedule): void
        {
            $schedule->command(RefreshLinksCommand::class)->dailyAt('04:00')->withoutOverlapping();
        });

        $this->registerServiceRecord();
        $this->addToSlot('account.profile.sections', 'holonet::account-link');

        $this->addSystemCheck(
            'Linked trackers: this tracker is on the list',
            fn (): bool => $this->app->make(TrackerDirectory::class)->listsThisTracker(),
        );
    }

    private function registerServiceRecord(): void
    {
        $linked = fn (array $context): array => [
            'linked_troops' => $this->app->make(LinkedTroopsOnPage::class)->for($context['trooper']),
        ];

        $this->addToSlot('service-records.trooper.summary', 'holonet::service-records.summary', $linked);
        $this->addToSlot('service-records.trooper.history', 'holonet::service-records.history', $linked);
        $this->addToSlot(
            'service-records.trooper.actions',
            'holonet::service-records.manage-button',
            when: fn (array $context): bool => Auth::id() === $context['trooper']->id,
        );
    }
}
