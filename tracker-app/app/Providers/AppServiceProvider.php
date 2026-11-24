<?php

declare(strict_types=1);

namespace App\Providers;

use App\Channels\FcmChannel;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Mail\Auth\VerifyTrooperEmail;
use App\Mail\OutgoingMailBudget;
use App\Models\TrooperRequest;
use App\Policies\TrooperRequestPolicy;
use App\Services\BreadCrumbService;
use App\View\Composers\NavbarNotificationsComposer;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\MailManager;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;
use Kreait\Firebase\Contract\Messaging;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Throwable;
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
        $this->app->scoped(BreadCrumbService::class, function (): BreadCrumbService
        {
            return new BreadCrumbService;
        });

        $this->app->bind(FcmChannel::class, function (Application $app): FcmChannel
        {
            try
            {
                $messaging = $app->make(Messaging::class);
            }
            catch (Throwable)
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
     * - Migration repository configuration
     * - Database blueprint macros (trooperstamps)
     * - Blade directives (@role)
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        $this->logMissingTranslations();
        $this->registerLocalFormat();

        Gate::policy(TrooperRequest::class, TrooperRequestPolicy::class);

        //
        //  Get all configured database connection keys (sqlite, mysql, pgsql, etc.)
        //  Disable savepoints globally across all database connections
        $connections = array_keys(config('database.connections', []));

        // Disable savepoints globally across every connection
        foreach ($connections as $connection)
        {
            config(["database.connections.{$connection}.use_savepoints" => false]);
        }

        //
        //  SMTP PING THRESHOLD
        //  Force the transport to test the connection with NOOP before sending if
        //  more than 10 seconds have elapsed since the last message, preventing
        //  "451 4.4.2 Timeout" failures on idle connections in long-running workers.
        //
        $this->app->afterResolving('mail.manager', function (MailManager $manager): void
        {
            $mailer = $manager->mailer();
            $transport = $mailer->getSymfonyTransport();
            if ($transport instanceof EsmtpTransport)
            {
                $transport->setPingThreshold(10);
            }
        });

        //
        //  OUTGOING MAIL BUDGET
        //  Count every recipient sent to, queued or not, so ThrottleOutgoingMail can
        //  hold queued mail under MAIL_RECIPIENTS_PER_HOUR.
        //
        Event::listen(MessageSending::class, function (MessageSending $event): void
        {
            $message = $event->message;

            app(OutgoingMailBudget::class)->record(
                count($message->getTo()) + count($message->getCc()) + count($message->getBcc())
            );
        });

        Paginator::useBootstrapFive();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url): VerifyTrooperEmail
        {
            return (new VerifyTrooperEmail($url))
                ->to($notifiable->email);
        });

        //
        //  MIGRATION
        //
        $this->app->extend(MigrationRepositoryInterface::class, function (MigrationRepositoryInterface $repository, Application $app): MigrationRepositoryInterface
        {
            return new DatabaseMigrationRepository(
                $app['db'],
                'tt_migrations'
            );
        });

        //
        //  DATABASE MIGRATION MACRO
        //
        Blueprint::macro('trooperstamps', function (): void
        {
            $this->unsignedBigInteger('created_id')->nullable();
            $this->unsignedBigInteger('updated_id')->nullable();
            $this->unsignedBigInteger('deleted_id')->nullable();
        });

        //
        //  VIEW COMPOSERS
        //
        View::composer('partials.inc.navbar-notifications', NavbarNotificationsComposer::class);

        //
        //  BLADE BOOTS
        //
        Blade::if('role', function (MembershipRole|string|array $roles): bool
        {
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

            $normalized = collect($roles)->map(function (MembershipRole|string $role): MembershipRole
            {
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

    /**
     * Locally, a key that no language defines (the page shows the raw key) is logged.
     */
    private function logMissingTranslations(): void
    {
        if (!$this->app->isLocal())
        {
            return;
        }

        Lang::handleMissingKeysUsing(function (string $key, array $replace, ?string $locale): null
        {
            Log::warning("Missing translation [{$locale}]: {$key}");

            return null;
        });
    }

    /**
     * $date->localFormat('M j, Y') formats with the current language's version of
     * that pattern from lang/<locale>/formats.php (keyed by the English pattern),
     * with month and day names translated.
     */
    private function registerLocalFormat(): void
    {
        $macro = function (string $format): string
        {
            $key = "formats.{$format}";
            $localized = Lang::has($key) ? __($key) : $format;

            /** @var Carbon|CarbonImmutable $this */
            return $this->translatedFormat($localized);
        };

        Carbon::macro('localFormat', $macro);
        CarbonImmutable::macro('localFormat', $macro);
    }
}
