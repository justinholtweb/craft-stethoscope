<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Settings;
use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class Searches extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Searches', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'cursor-arrow-rays';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->rows(Stethoscope::SEARCH, ['count', 'avg', 'max'], 1);
        $empty = $this->counts(Stethoscope::SEARCH_EMPTY);

        return [
            'tiles' => [
                [$this->scaled($rows->sum('count')), t('Searches', category: 'stethoscope')],
                [$this->scaled($rows->sum(fn (object $r): float => $empty[$r->key] ?? 0)), t('No results', category: 'stethoscope')],
            ],
            'note' => app(Settings::class)->recordSearchTerms ? null : t('Search terms aren’t recorded (Stethoscope settings).', category: 'stethoscope'),
            'tables' => [[
                'headers' => [self::header(t('Search', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Count', category: 'stethoscope'), true), self::header(t('No results', category: 'stethoscope'), true), self::header(t('Average', category: 'stethoscope'), true), self::header(t('Slowest', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code($r->parts[2] ?? $r->parts[0] ?? null, isset($r->parts[2]) ? $r->parts[0] : null),
                    self::text(self::siteLabel($r->parts[1] ?? null)),
                    self::num($this->scaled($r->count)),
                    self::num($this->scaled($empty[$r->key] ?? 0)),
                    self::num(self::ms($r->avg)),
                    self::num(self::ms($r->max), true),
                ]])->all(),
            ]],
        ];
    }
}
