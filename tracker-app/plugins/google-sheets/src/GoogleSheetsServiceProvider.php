<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\GoogleSheets;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Plugins\PluginForms;
use App\Plugins\PluginServiceProvider;
use Illuminate\Support\Str;
use TroopTracker\Plugins\GoogleSheets\Models\OrganizationSheet;
use TroopTracker\Plugins\GoogleSheets\Services\GoogleSheetsClient;

class GoogleSheetsServiceProvider extends PluginServiceProvider
{
    private const DEFAULT_CREDENTIALS = 'google-credentials.json';

    protected function registerPlugin(): void
    {
        $this->app->bind(
            GoogleSheetsClient::class,
            fn (): GoogleSheetsClient => new GoogleSheetsClient($this->credentialsPath()),
        );
    }

    public function bootPlugin(): void
    {
        $this->setSheetSource(GoogleSheetSource::class);
        $this->addSheetField();

        $this->addSystemCheck(
            'Google Sheets credentials',
            fn (): bool => $this->app->make(GoogleSheetsClient::class)->isConfigured(),
        );
    }

    private function addSheetField(): void
    {
        $this->addToSlot(
            'admin.organizations.update.fields',
            'google-sheets::admin.organization-sheet-field',
            fn (array $context): array => [
                'google_sheet_id' => OrganizationSheet::forOrganization(
                    $context['organization']->id
                ),
            ],
            fn (array $context): bool => $context['organization']->type
                === OrganizationType::ORGANIZATION,
        );

        $this->extendForm(
            PluginForms::ORGANIZATION_UPDATE,
            rules: ['google_sheet_id' => ['nullable', 'string', 'max:128']],
            saved: function (Organization $organization, array $validated): void
            {
                if (array_key_exists('google_sheet_id', $validated))
                {
                    OrganizationSheet::store($organization->id, $validated['google_sheet_id']);
                }
            },
        );
    }

    private function credentialsPath(): string
    {
        $path = (string) ($this->setting('credentials_path') ?: self::DEFAULT_CREDENTIALS);

        $is_absolute = Str::startsWith($path, ['/', '\\'])
            || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;

        return $is_absolute ? $path : base_path($path);
    }
}
