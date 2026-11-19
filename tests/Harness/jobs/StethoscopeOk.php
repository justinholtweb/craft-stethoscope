<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/** Stethoscope harness job that succeeds (copied to the harness's app/Jobs by check.sh). */
class StethoscopeOk implements ShouldQueue
{
    use Queueable;

    public function handle(): void {}
}
