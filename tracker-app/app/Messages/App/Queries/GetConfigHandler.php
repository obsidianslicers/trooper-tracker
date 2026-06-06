<?php

declare(strict_types=1);

namespace App\Messages\App\Queries;

use BackedEnum;
use App\Facades\TroopTracker;
use App\Messages\MessageHandler;
use Illuminate\Support\Facades\File;

/**
 * Retrieves application configuration including authentication provider status and feature toggles.
 *
 * This query message responds with configuration data for authorization providers (XenForo OAuth,
 * Google OAuth, email/password authentication), application metadata, and feature/localization settings.
 * Used by frontend clients to determine available authentication methods and application capabilities.
 */
final class GetConfigHandler extends MessageHandler
{
    /**
     * Retrieves application configuration as a nested associative array.
     *
     * Returns a configuration structure containing:
     * - `meta`: Application metadata (`env`, `name`)
     * - `auth`: Authentication provider configuration with nested providers:
     *   - `xenforo`: Keys `required`, `enabled`, `configured` (bool), `url` (string if configured)
     *   - `google`: Keys `enabled`, `configured` (bool), `url` (string if configured)
     *   - `email_password`: Key `enabled` (bool)
     * - `features`: Feature toggle flags (currently empty)
     * - `localization`: Localization configuration (currently empty)
     *
     * @param GetConfig $message The GetConfig query message instance
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(object $message): array
    {
        $data = [
            'branding' => [
                'name' => config('app.name'),
            ],
            'meta' => [
                'env' => config('app.env'),
                'base_url' => config('app.url'),
            ],
        ];

        return $data;
    }

    /**
     * Build a map of enum class name => select options.
     *
     * @return array<string, array<int, array{value: string, label: string}>>
     */
    private function getEnumConfig(): array
    {
        $enums = [];
        $enum_files = File::files(app_path('Enums'));

        foreach ($enum_files as $enum_file)
        {
            $enum_name = pathinfo($enum_file->getFilename(), PATHINFO_FILENAME);
            $enum_class = "App\\Enums\\{$enum_name}";

            if (!enum_exists($enum_class))
            {
                continue;
            }

            if (method_exists($enum_class, 'toArray'))
            {
                /** @var array<string, string> $enum_values */
                $enum_values = $enum_class::toArray();
                $enum_options = [];

                foreach ($enum_values as $enum_value => $enum_label)
                {
                    $enum_options[] = [
                        'value' => $enum_value,
                        'label' => $enum_label,
                    ];
                }

                $enums[$enum_name] = $enum_options;
                continue;
            }

            $enum_options = [];
            $cases = $enum_class::cases();

            usort($cases, fn($a, $b) => strcmp($a->name, $b->name));

            foreach ($cases as $case)
            {
                if (!$case instanceof BackedEnum)
                {
                    continue;
                }

                $enum_options[] = [
                    'value' => $case->value,
                    'label' => to_title($case->name)->toString(),
                ];
            }

            $enums[$enum_name] = $enum_options;
        }

        ksort($enums);

        return $enums;
    }
}