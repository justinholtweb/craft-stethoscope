<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class ElementQueries extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Element queries', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'circle-stack';
    }

    #[Override]
    protected function data(): array
    {
        $pages = $this->rows(Stethoscope::PAGE_QUERIES, ['count', 'avg', 'max'], 1, $this->sort() === 'count' ? 'max' : $this->sort());
        $types = $this->rows(Stethoscope::ELEMENT_QUERY, ['count'], 2);

        return [
            'tables' => [
                [
                    'heading' => t('Per page render', category: 'stethoscope'),
                    'headers' => [self::header(t('Template', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Renders', category: 'stethoscope'), true), self::header(t('Average', category: 'stethoscope'), true), self::header(t('Most', category: 'stethoscope'), true)],
                    'rows' => $pages->take(50)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                        self::code($r->parts[0] ?? null),
                        self::text(self::siteLabel($r->parts[1] ?? null)),
                        self::num($this->scaled($r->count)),
                        self::num(number_format($r->avg, 1)),
                        self::num(number_format($r->max), true),
                    ]])->all(),
                ],
                [
                    'heading' => t('By element type', category: 'stethoscope'),
                    'headers' => [self::header(t('Elements', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Queries', category: 'stethoscope'), true)],
                    'rows' => $types->take(50)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                        self::code($r->parts[0] === null ? t('(no results)', category: 'stethoscope') : self::label($r->parts[0], $r->parts[1] ?? null)),
                        self::text(self::siteLabel($r->parts[2] ?? null)),
                        self::num($this->scaled($r->count), true),
                    ]])->all(),
                ],
            ],
        ];
    }
}
