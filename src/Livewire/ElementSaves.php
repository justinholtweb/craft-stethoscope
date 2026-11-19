<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class ElementSaves extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Element saves', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'arrow-trending-up';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->rows(Stethoscope::ELEMENT_SAVE, ['count', 'avg', 'max'], 2);
        $live = $rows->filter(fn (object $r): bool => ($r->parts[3] ?? null) !== 'draft')->sum('count');
        $drafts = $rows->sum('count') - $live;

        return [
            'tiles' => [
                [$this->scaled($live), t('Live saves', category: 'stethoscope')],
                [$this->scaled($drafts), t('Draft saves', category: 'stethoscope')],
            ],
            'tables' => [[
                'headers' => [self::header(t('Element', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Saves', category: 'stethoscope'), true), self::header(t('Average', category: 'stethoscope'), true), self::header(t('Slowest', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code(self::label($r->parts[0] ?? null, $r->parts[1] ?? null), ($r->parts[3] ?? null) === 'draft' ? t('drafts', category: 'stethoscope') : null),
                    self::text(self::siteLabel($r->parts[2] ?? null)),
                    self::num($this->scaled($r->count), true),
                    self::num(self::ms($r->avg)),
                    self::num(self::ms($r->max)),
                ]])->all(),
            ]],
        ];
    }
}
