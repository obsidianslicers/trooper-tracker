<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Messages\App\Queries\GetConfig;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template loaded on the first Inertia page visit.
     */
    protected $rootView = 'layouts.inertia';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $config = GetConfig::call();

        if (Auth::check())
        {
            $user = $request->user();

            $actor = [
                Trooper::ID => $user->id,
                Trooper::LEGAL_NAME => $user->legal_name,
                Trooper::DISPLAY_NAME => $user->display_name,
                Trooper::EMAIL => $user->email,
            ];
        }
        else
        {
            $actor = [
                Trooper::ID => null,
                Trooper::LEGAL_NAME => null,
                Trooper::DISPLAY_NAME => null,
                Trooper::EMAIL => null,
            ];
        }

        return [
            ...parent::share($request),
            'config' => $config,
            'user' => $actor,
            'results' => fn() => $request->session()->pull('results'),
            'flash' => function () use ($request)
            {
                $messages = [];

                foreach (['success', 'info', 'warning', 'danger'] as $type)
                {
                    $msg = $request->session()->pull($type);

                    if ($msg)
                    {
                        $messages[$type][] = $msg;
                    }
                }

                $custom_messages = app(FlashMessageService::class)->getMessages();

                foreach ($custom_messages as $type => $type_messages)
                {
                    foreach ($type_messages as $text)
                    {
                        $messages[$type][] = $text;
                    }
                }

                return $messages;
            },
        ];
    }
}
