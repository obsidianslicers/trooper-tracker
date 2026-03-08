<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Enums\TrooperTheme;
use App\Features\Troopers\Commands\UpdateTrooperCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ProfileRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Handles form submission for updating the authenticated trooper's profile.
 *
 * This controller validates profile data via ProfileRequest, dispatches
 * UpdateTrooperCommand to persist changes, and redirects back to the profile page.
 */
class ProfileSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to update the trooper's profile.
     *
     * @param  ProfileRequest  $request  The validated profile update request
     * @return RedirectResponse Redirect to the profile page with success message
     */
    public function __invoke(ProfileRequest $request): RedirectResponse
    {
        $trooper = $request->user();

        $orginal_theme = $trooper->theme->value;

        $update_cmd = new UpdateTrooperCommand($trooper, $request->validated());

        $this->bus->send($update_cmd);

        $this->flash->updated($trooper);

        if ($orginal_theme != $request->validated('theme'))
        {
            $theme_message = $this->getThemeMessage(TrooperTheme::from($request->validated('theme')));

            $this->flash->warning($theme_message);
        }

        return redirect()->route('account.profile');
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
