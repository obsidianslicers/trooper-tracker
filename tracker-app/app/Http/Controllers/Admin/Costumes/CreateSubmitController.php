<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Costumes;

use App\Enums\CostumeType;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Admin\Costumes\CreateRequest;
use App\Models\Costume;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

/**
 * Handles submission of the form for creating a new costume.
 *
 * An invokable controller that validates the request, creates a new costume,
 * and redirects to the update page with a success message.
 */
class CreateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to create a new costume.
     *
     * Validates the request, creates a new costume with the provided name,
     * saves it, resequences costumes, and redirects to the update page.
     *
     * @param CreateRequest $request The validated request containing the new costume's data.
     * @param Costume $parent The parent costume (route model binding).
     * @return RedirectResponse A redirect response to the costume update page.
     */
    public function __invoke(CreateRequest $request, Costume $parent): RedirectResponse
    {
        $costume = new Costume;

        $costume->name = $request->validated('name');

        $costume->save();

        Costume::resequenceAll();

        $this->flash->created($costume);

        return redirect()->route('admin.costumes.update', compact('costume'));
    }
}
