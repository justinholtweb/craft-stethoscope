<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use RuntimeException;

/** Stethoscope harness job that fails (copied to the harness's app/Jobs by check.sh). */
class StethoscopeFail implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(): void
    {
        throw new RuntimeException('Stethoscope check: deliberate failure');
    }
}
