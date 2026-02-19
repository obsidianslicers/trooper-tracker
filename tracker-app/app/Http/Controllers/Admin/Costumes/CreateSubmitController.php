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
 * Class CreateSubmitController
 *
 * Handles the submission of the form for creating a new costume.
 */
class CreateSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to create a new costume
     *
     * Validates the request, creates a new costume under the given parent,
     * determines its type, saves it, and then redirects with a success message.
     *
     * @param  CreateRequest  $request  The validated request containing the new costume's data
     * @param  Costume  $parent  The parent costume
     * @return RedirectResponse A redirect response to the costume list
     *
     * @throws InvalidArgumentException If parent type doesn't allow child costumes
     */
    public function __invoke(CreateRequest $request, Costume $parent): RedirectResponse
    {
        $costume = new Costume;

        $costume->parent_id = $parent->id;
        $costume->name = $request->validated('name');

        if ($parent->type == CostumeType::ORGANIZATION)
        {
            $costume->type = CostumeType::REGION;
        }
        elseif ($parent->type == CostumeType::REGION)
        {
            $costume->type = CostumeType::UNIT;
        }
        else
        {
            throw new InvalidArgumentException('Cannot create a sub-costume under the specified parent type.');
        }

        $costume->save();

        Costume::resequenceAll();

        $this->flash->created($costume);

        return redirect()->route('admin.costumes.update', compact('costume'));
    }
}
