<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use JustinHoltWeb\Stethoscope\Stethoscope;

/**
 * Queue jobs by class and queue: how many ran, how long they took, how many
 * failed. Only the class name is recorded, never the job's description or
 * payload (those can name elements or people).
 */
class QueueJobs
{
    /** @var array<string, int> job id → hrtime */
    private array $started = [];

    public function __construct(private readonly Stethoscope $stethoscope) {}

    public function processing(JobProcessing $event): void
    {
        $this->started[self::id($event->job)] = hrtime(true);
    }

    public function processed(JobProcessed $event): void
    {
        $this->finish($event->job, failed: false);
    }

    public function failed(JobFailed $event): void
    {
        $this->finish($event->job, failed: true);
    }

    private function finish(Job $job, bool $failed): void
    {
        $id = self::id($job);
        $start = $this->started[$id] ?? null;
        unset($this->started[$id]);

        // Released jobs fire neither; a job processed twice is two runs.
        if ($start === null || ! $this->stethoscope->sampled()) {
            return;
        }

        $key = [self::name($job), $job->getQueue()];
        $this->stethoscope->record(Stethoscope::JOB, $key, Stethoscope::since($start), ['count', 'avg', 'max']);

        if ($failed) {
            $this->stethoscope->record(Stethoscope::JOB_FAILED, $key);
        }
    }

    public static function name(Job $job): string
    {
        $name = $job->resolveName();

        // Only class names; a closure job's name can be arbitrary text.
        return preg_match('/^[A-Za-z_\\\\][\w\\\\]*$/D', $name) ? $name : 'Closure';
    }

    private static function id(Job $job): string
    {
        return $job->getConnectionName().':'.$job->getJobId();
    }
}
