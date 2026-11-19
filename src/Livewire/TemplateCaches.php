<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use JustinHoltWeb\Stethoscope\Stethoscope;
use JustinHoltWeb\Stethoscope\Support\Key;
use Livewire\Attributes\Lazy;
use Override;

use function CraftCms\Cms\t;

#[Lazy]
class TemplateCaches extends StethoscopeCard
{
    #[Override]
    protected function title(): string
    {
        return t('Template caches', category: 'stethoscope');
    }

    #[Override]
    protected function icon(): string
    {
        return 'rocket-launch';
    }

    #[Override]
    protected function data(): array
    {
        $rows = $this->aggregateTypes([Stethoscope::CACHE_HIT, Stethoscope::CACHE_MISS], 'count', null, 'desc', 500)
            ->map(fn (object $row): object => (object) [
                'key' => (string) $row->key,
                'parts' => Key::decode((string) $row->key),
                'hits' => (float) ($row->{Stethoscope::CACHE_HIT} ?? 0),
                'misses' => (float) ($row->{Stethoscope::CACHE_MISS} ?? 0),
            ])
            ->filter(fn (object $r): bool => $this->siteHandle() === null || ($r->parts[1] ?? null) === $this->siteHandle())
            ->values();
        $hits = $rows->sum('hits');
        $misses = $rows->sum('misses');

        return [
            'tiles' => [
                [$this->scaled($hits), t('Hits', category: 'stethoscope')],
                [$this->scaled($misses), t('Misses', category: 'stethoscope')],
                [self::rateOf($hits, $misses), t('Hit rate', category: 'stethoscope')],
            ],
            'tables' => [[
                'headers' => [self::header(t('Cache', category: 'stethoscope')), self::header(t('Site', category: 'stethoscope')), self::header(t('Hits', category: 'stethoscope'), true), self::header(t('Misses', category: 'stethoscope'), true), self::header(t('Hit rate', category: 'stethoscope'), true)],
                'rows' => $rows->take(100)->map(fn (object $r): array => ['key' => $r->key, 'cells' => [
                    self::code($r->parts[0] ?? null),
                    self::text(self::siteLabel($r->parts[1] ?? null)),
                    self::num($this->scaled($r->hits)),
                    self::num($this->scaled($r->misses)),
                    self::num(self::rateOf($r->hits, $r->misses), true),
                ]])->all(),
            ]],
        ];
    }

    #[Override]
    protected function sortable(): bool
    {
        return false;
    }

    private static function rateOf(float $hits, float $misses): string
    {
        return $hits + $misses > 0 ? number_format($hits / ($hits + $misses) * 100, 1).'%' : '—';
    }
}
