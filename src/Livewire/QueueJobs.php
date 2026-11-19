<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class QueueJobs extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Queue jobs', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'queue-list';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->rows(Stethoscope::JOB, ['count', 'avg', 'max'], null);
        $failed = $this->counts(Stethoscope::JOB_FAILED);

        return [
            'tiles' => [
                [$this->scaled($rows->sum('count')), t('Processed', category: 'stethoscope')],
                [$this->scaled(array_sum($failed)), t('Failed', category: 'stethoscope')],
            ],
            'tables' => [[
                'headers' => [self::header(t('Job', category: 'stethoscope')), self::header(t('Queue', category: 'stethoscope')), self::header(t('Runs', category: 'stethoscope'), true), self::header(t('Failed', category: 'stethoscope'), true), self::header(t('Average', category: 'stethoscope'), true), self::header(t('Slowest', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code($r->parts[0] ?? null),
                    self::text($r->parts[1] ?? null),
                    self::num($this->scaled($r->count)),
                    self::num($this->scaled($failed[$r->key] ?? 0)),
                    self::num(self::ms($r->avg)),
                    self::num(self::ms($r->max), true),
                ]])->all(),
            ]],
        ];
    }
}
