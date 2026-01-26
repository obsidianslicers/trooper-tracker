<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Reports;

use App\Http\Controllers\MagicBusController;

class BaseReportsController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }
}
