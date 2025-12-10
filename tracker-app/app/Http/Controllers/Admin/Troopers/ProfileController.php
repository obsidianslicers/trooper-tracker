<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class ProfileController
 *
 * Handles the display of a single trooper's profile page.
 * @package App\Http\Controllers\Admin\Troopers
 */
class ProfileController extends Controller
{
    /**
     * ProfileController constructor.
     *
     * @param BreadCrumbService $crumbs The breadcrumb service for managing navigation history.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's profile page.
     *
     * This method authorizes the user, sets up breadcrumbs, and returns the view
     * for a specific trooper's profile.
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The trooper whose profile is to be displayed.
     * @return View The rendered profile page view.
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $data = compact('trooper');

        return view('pages.admin.troopers.profile', $data);
    }
}
