<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use JustinHoltWeb\Stethoscope\Http\Controllers\DashboardController;
use JustinHoltWeb\Stethoscope\Plugin;
use Laravel\Pulse\Http\Middleware\Authorize;

// The cp group already applies auth + can:accessCp. Pulse's Authorize middleware is
// listed so Livewire re-checks the `viewPulse` gate (mapped to the same permission)
// on every card refresh, not just on the first page load.
Route::get('stethoscope', DashboardController::class)
    ->middleware(['can:accessPlugin-stethoscope', 'can:'.Plugin::PERMISSION, Authorize::class])
    ->name('stethoscope.dashboard');
