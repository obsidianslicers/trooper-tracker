<?php

declare(strict_types=1);

namespace App\Providers;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OauthProvider;
use App\Mail\Auth\VerifyTrooperEmail;
use App\Services\BreadCrumbService;
use App\Services\Notifications\PushNotificationService;
use Kreait\Firebase\Contract\Messaging;
use App\Services\Socialite\XenforoProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Laravel\Socialite\Facades\Socialite;
use ValueError;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register application services.
     *
     * Registers scoped bindings for services like BreadCrumbService.
     */
    public function register(): void
    {
        $this->app->scoped(BreadCrumbService::class, function () {
            return new BreadCrumbService;
        });

        $this->app->bind(PushNotificationService::class, function ($app) {
            try {
                $messaging = $app->make(Messaging::class);
            } catch (\Throwable) {
                $messaging = null;
            }

            return new PushNotificationService($messaging);
        });
    }

    /**
     * Bootstrap application services.
     *
     * Sets up:
     * - Bootstrap 5 pagination
     * - HTMX request detection macro
     * - Custom Socialite providers (XenForo)
     * - Migration repository configuration
     * - Database blueprint macros (trooperstamps)
     * - Blade directives (@role)
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new VerifyTrooperEmail($url))
                ->to($notifiable->email);
        });

        //
        //  HTMX REQUEST MACRO
        //
        Request::macro('isHtmx', function () {
            return $this->headers->has('HX-Request');
        });

        //
        //  SOCIALITE CUSTOM PROVIDERS
        //
        Socialite::extend(OauthProvider::XENFORO->value, function ($app) {
            $config = $app['config']['services.xenforo'];

            return Socialite::buildProvider(XenforoProvider::class, $config);
        });

        //
        //  MIGRATION
        //
        $this->app->extend(MigrationRepositoryInterface::class, function ($repository, $app) {
            return new DatabaseMigrationRepository(
                $app['db'],
                'tt_migrations'
            );
        });

        //
        //  DATABASE MIGRATION MACRO
        //
        Blueprint::macro('trooperstamps', function () {
            $this->unsignedBigInteger('created_id')->nullable();
            $this->unsignedBigInteger('updated_id')->nullable();
            $this->unsignedBigInteger('deleted_id')->nullable();
        });

        //
        //  BLADE BOOTS
        //
        Blade::if('role', function (MembershipRole|string|array $roles): bool {
            if (!Auth::check())
            {
                return false;
            }

            $user = Auth::user();

            if (!$user || $user->membership_status !== MembershipStatus::ACTIVE)
            {
                return false;
            }

            // Normalize roles into an array of MembershipRole enums
            if (is_string($roles))
            {
                $roles = array_map('trim', explode(',', $roles));
            }

            $normalized = collect($roles)->map(function ($role) {
                if ($role instanceof MembershipRole)
                {
                    return $role;
                }

                try
                {
                    return MembershipRole::from($role);
                }
                catch (ValueError $e)
                {
                    throw new InvalidArgumentException("Invalid permission role: '{$role}'");
                }
            });

            return $normalized->contains($user->membership_role);
        });
    }
}
