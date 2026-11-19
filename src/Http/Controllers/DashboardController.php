<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\View as ViewFactory;

/**
 * The Stethoscope dashboard (Pulse's Livewire page with Craft's cards), at
 * `admin/stethoscope`. routes/cp.php puts it behind the CP login, CP access,
 * `stethoscope:viewDashboard` and Pulse's own Authorize middleware (which
 * Livewire re-runs on every card refresh).
 */
class DashboardController
{
    public function __invoke(): View
    {
        return ViewFactory::make('stethoscope::dashboard');
    }
}
