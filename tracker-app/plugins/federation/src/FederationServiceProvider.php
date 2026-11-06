<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation;

use App\Plugins\PluginServiceProvider;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Auth;
use TroopTracker\Plugins\Federation\Console\RefreshLinksCommand;
use TroopTracker\Plugins\Federation\Directory\TrackerDirectory;
use TroopTracker\Plugins\Federation\Http\Controllers\ExchangeLinkCodeController;
use TroopTracker\Plugins\Federation\Http\Controllers\ForgetAccessKeyController;
use TroopTracker\Plugins\Federation\Http\Controllers\ShareTroopHistoryController;
use TroopTracker\Plugins\Federation\Support\LinkedTroopsOnPage;
use TroopTracker\Plugins\Federation\Support\ThisTracker;
use TroopTracker\Plugins\Federation\Support\TrackerClient;

class FederationServiceProvider extends PluginServiceProvider
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

        $this->addWebhook('federation/token', ExchangeLinkCodeController::class, 'federation.token');
        $this->addWebhook('federation/history', ShareTroopHistoryController::class, 'federation.history');
        $this->addWebhook('federation/revoke', ForgetAccessKeyController::class, 'federation.revoke');

        $this->schedule(function (Schedule $schedule): void
        {
            $schedule->command(RefreshLinksCommand::class)->dailyAt('04:00')->withoutOverlapping();
        });

        $this->registerServiceRecord();
        $this->addToSlot('account.profile.sections', 'federation::account-link');

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

        $this->addToSlot('service-records.trooper.summary', 'federation::service-records.summary', $linked);
        $this->addToSlot('service-records.trooper.history', 'federation::service-records.history', $linked);
        $this->addToSlot(
            'service-records.trooper.actions',
            'federation::service-records.manage-button',
            when: fn (array $context): bool => Auth::id() === $context['trooper']->id,
        );
    }
}
