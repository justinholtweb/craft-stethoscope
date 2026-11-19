<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Stethoscope;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class Transforms extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Image transforms', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'sparkles';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->rows(Stethoscope::TRANSFORM, ['count'], null, 'count');

        return [
            'tiles' => [[$this->scaled($rows->sum('count')), t('Generated', category: 'stethoscope')]],
            'tables' => [[
                'headers' => [self::header(t('Transform', category: 'stethoscope')), self::header(t('Volume', category: 'stethoscope')), self::header(t('Generated', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code($r->parts[0] ?? null),
                    self::text($r->parts[1] ?? null),
                    self::num($this->scaled($r->count), true),
                ]])->all(),
            ]],
        ];
    }

    #[Override]
    protected function sortable(): bool
    {
        return false;
    }
}
