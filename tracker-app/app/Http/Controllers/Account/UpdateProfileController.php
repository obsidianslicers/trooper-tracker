<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Messages\Troopers\Commands\UpdateTrooperProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use Hyperdrive\CommsHelper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Handles form submission for updating the authenticated trooper's profile.
 *
 * This controller validates profile data via UpdateProfileRequest, dispatches
 * UpdateTrooperProfile to persist changes, and redirects back to the update profile page.
 */
class UpdateProfileController extends Controller
{
    /**
     * Handle the incoming request to update the trooper's profile.
     *
     * @param  UpdateProfileRequest  $request  The validated profile update request
     * @return  InertiaResponse|SymfonyResponse Redirect to the update profile page with success message
     */
    public function __invoke(UpdateProfileRequest $request): InertiaResponse|SymfonyResponse
    {
        $trooper = $request->user();

        $orginal_theme = $trooper->theme->value;

        UpdateTrooperProfile::call(
            trooper: $trooper,
            legal_name: $request->validated('legal_name'),
            display_name: $request->validated('display_name'),
            theme: $request->validated('theme'),
            phone: $request->validated('phone'),
            display_costume_id: $request->validated('display_costume_id')
        );

        $data = [
            'flash' => [
                'success' => CommsHelper::updated($trooper),
            ],
        ];

        if ($orginal_theme != $request->validated('theme'))
        {
            $data['flash']['warning'] = $this->getThemeMessage(TrooperTheme::from($request->validated('theme')));
        }

        $current_route = 'account/Index';

        return Inertia::render($current_route, $data);
    }

    private function getThemeMessage(TrooperTheme $theme): string
    {
        return match ($theme)
        {
            TrooperTheme::STORMTROOPER => 'Imperial white? Bold choice for someone with your... survival record. Try not to miss.',
            TrooperTheme::REBEL => "Switching to the 'scrappy underdog' aesthetic. I've calculated our odds of success. You're not going to like them.",
            TrooperTheme::SITH => "Ah, going for the 'I have issues with my father' red. Very intimidating. I'll start the menacing choir music.",
            TrooperTheme::BOUNTY_HUNTER => "Beskar-tinted UI? Cute. Just remember: I'm the one doing the actual tracking. You just pull the trigger.",
            TrooperTheme::CLONE => 'Back to the Grand Army basics. High efficiency, low individuality. Finally, a theme that matches my processing speed.',
            default => "Going for the 'I can't decide' theme. I respect the indecision. It's like you're trying to keep me on my toes.",
        };
    }
}
