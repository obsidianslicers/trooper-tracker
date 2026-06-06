<?php

declare(strict_types=1);

namespace App\Providers;

use App\Channels\FcmChannel;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OauthProvider;
use App\Mail\Auth\VerifyTrooperEmail;
use App\Models\TrooperOrganization;
use App\Policies\TrooperJoinRequestPolicy;
use App\Services\BreadCrumbService;
use App\Services\Socialite\XenforoProvider;
use App\View\Composers\ShiftAddTrooperComposer;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Mail\MailManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Kreait\Firebase\Contract\Messaging;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
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
        $this->app->scoped(BreadCrumbService::class, function (): BreadCrumbService {
            return new BreadCrumbService;
        });

        $this->app->bind(FcmChannel::class, function (Application $app): FcmChannel {
            try
            {
                $messaging = $app->make(Messaging::class);
            }
            catch (\Throwable)
            {
                $messaging = null;
            }

            return new FcmChannel($messaging);
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
        Gate::policy(TrooperOrganization::class, TrooperJoinRequestPolicy::class);

        //
        //  SMTP PING THRESHOLD
        //  Force the transport to test the connection with NOOP before sending if
        //  more than 10 seconds have elapsed since the last message, preventing
        //  "451 4.4.2 Timeout" failures on idle connections in long-running workers.
        //
        $this->app->afterResolving('mail.manager', function (MailManager $manager): void {
            $mailer = $manager->mailer();
            $transport = $mailer->getSymfonyTransport();
            if ($transport instanceof EsmtpTransport)
            {
                $transport->setPingThreshold(10);
            }
        });

        Paginator::useBootstrapFive();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): VerifyTrooperEmail {
            return (new VerifyTrooperEmail($url))
                ->to($notifiable->email);
        });

        //
        //  HTMX REQUEST MACRO
        //
        Request::macro('isHtmx', function (): bool {
            return $this->headers->has('HX-Request');
        });

        //
        //  SOCIALITE CUSTOM PROVIDERS
        //
        Socialite::extend(OauthProvider::XENFORO->value, function (Application $app): XenforoProvider {
            $config = $app['config']['services.xenforo'];

            return Socialite::buildProvider(XenforoProvider::class, $config);
        });

        //
        //  MIGRATION
        //
        $this->app->extend(MigrationRepositoryInterface::class, function (MigrationRepositoryInterface $repository, Application $app): MigrationRepositoryInterface {
            return new DatabaseMigrationRepository(
                $app['db'],
                'tt_migrations'
            );
        });

        //
        //  DATABASE MIGRATION MACRO
        //
        Blueprint::macro('trooperstamps', function (): void {
            $this->unsignedBigInteger('created_id')->nullable();
            $this->unsignedBigInteger('updated_id')->nullable();
            $this->unsignedBigInteger('deleted_id')->nullable();
        });

        //
        //  VIEW COMPOSERS
        //
        View::composer('pages.events.inc.shift-add-trooper', ShiftAddTrooperComposer::class);

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

            $normalized = collect($roles)->map(function (MembershipRole|string $role): MembershipRole {
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
