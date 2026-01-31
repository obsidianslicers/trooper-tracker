<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Controller for displaying the reports menu.
 *
 * Shows the main reports landing page with links to various
 * administrative reports.
 */
class ReportDisplayController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Display the reports menu page.
     *
     * @param  Request  $request  The HTTP request.
     * @return View The reports menu view.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.admin.reports.display');
    }
}
