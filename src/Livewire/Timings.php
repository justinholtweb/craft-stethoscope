<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class Timings extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Template timings', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'scale';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->rows(Stethoscope::TIMING, ['count', 'avg', 'max'], 1);

        return [
            'note' => t('Blocks wrapped in {tag} or {directive}.', ['tag' => '{% stethoscope %}', 'directive' => '@stethoscope'], category: 'stethoscope'),
            'tables' => [[
                'headers' => [self::header(t('Label', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Runs', category: 'stethoscope'), true), self::header(t('Average', category: 'stethoscope'), true), self::header(t('Slowest', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code($r->parts[0] ?? null, $r->parts[2] ?? null),
                    self::text(self::siteLabel($r->parts[1] ?? null)),
                    self::num($this->scaled($r->count)),
                    self::num(self::ms($r->avg)),
                    self::num(self::ms($r->max), true),
                ]])->all(),
            ]],
        ];
    }
}
