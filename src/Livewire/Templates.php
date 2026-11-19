<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Settings;
use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class Templates extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Template renders', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'clock';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->rows(Stethoscope::RENDER, ['count', 'avg', 'max'], 1);
        $slow = $this->counts(Stethoscope::SLOW_RENDER);

        return [
            'note' => t('Slow = {ms} ms or more.', ['ms' => app(Settings::class)->slowTemplateThreshold()], category: 'stethoscope'),
            'tables' => [[
                'headers' => [self::header(t('Template', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Renders', category: 'stethoscope'), true), self::header(t('Slow', category: 'stethoscope'), true), self::header(t('Average', category: 'stethoscope'), true), self::header(t('Slowest', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code($r->parts[0] ?? null, ($r->parts[2] ?? null) === 'partial' ? t('partial', category: 'stethoscope') : null),
                    self::text(self::siteLabel($r->parts[1] ?? null)),
                    self::num($this->scaled($r->count)),
                    self::num($this->scaled($slow[$r->key] ?? 0)),
                    self::num(self::ms($r->avg)),
                    self::num(self::ms($r->max), true),
                ]])->all(),
            ]],
        ];
    }
}
