<?php

declare(strict_types=1);

namespace App\Messages\Home\PageData;

use App\Plugins\PluginLoginProviders;
use Hyperdrive\Message;

/**
 * The home page's login button: the login page, or straight to the provider everyone must use.
 *
 * @method static array call()
 */
final class HomePageData extends Message
{
    public function handle(PluginLoginProviders $providers): array
    {
        $required = $providers->required();

        return [
            'login_url' => $required === null
                ? route('auth.login')
                : route('auth.oauth-redirect', ['provider' => $required->key()]),
            'login_label' => $required === null
                ? __('layout.Login')
                : __('auth.Login with :provider', ['provider' => $required->name()]),
        ];
    }
}
