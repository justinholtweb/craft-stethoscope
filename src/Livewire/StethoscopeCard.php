<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Livewire;

use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use JustinHoltWeb\Stethoscope\Settings;
use JustinHoltWeb\Stethoscope\Support\Key;
use Laravel\Pulse\Livewire\Card;
use Livewire\Attributes\Url;
use Throwable;

use function CraftCms\Cms\t;

/**
 * Base for Stethoscope's Pulse cards: the site filter (`?site=`, shared by every
 * card), sort order, key decoding, sample-rate scaling, and one shared view
 * (`stethoscope::livewire.card`) fed with plain table data.
 */
abstract class StethoscopeCard extends Card
{
    /** A site handle, or '' for every site. Validated before use. */
    #[Url]
    public string $site = '';

    /** 'count', 'avg' or 'max' — cards that time things offer the choice. */
    public string $orderBy = 'count';

    abstract protected function title(): string;

    /** One of Pulse's icon components (`pulse::icons.*`). */
    abstract protected function icon(): string;

    /**
     * @return array{tables: list<array{heading?: string|null, headers: list<array{0: string, 1: bool}>, rows: list<array{key: string, cells: list<array<string, mixed>>}>}>, tiles?: list<array{0: string, 1: string}>, note?: string|null}
     */
    abstract protected function data(): array;

    /** Whether the card offers sorting by count/average/slowest. */
    protected function sortable(): bool
    {
        return true;
    }

    public function render(): Renderable
    {
        [$data, $time, $runAt] = $this->remember(fn () => $this->data(), $this->siteHandle().':'.$this->sort());

        return View::make('stethoscope::livewire.card', [
            'title' => $this->title(),
            'icon' => $this->icon(),
            'details' => $this->details(),
            'sortable' => $this->sortable(),
            'sortOptions' => [
                'count' => t('count', category: 'stethoscope'),
                'avg' => t('average', category: 'stethoscope'),
                'max' => t('slowest', category: 'stethoscope'),
            ],
            'data' => $data,
            'time' => $time,
            'runAt' => $runAt,
        ]);
    }

    protected function details(): string
    {
        $parts = [t('past {period}', ['period' => $this->periodForHumans()], category: 'stethoscope')];

        if (($site = $this->siteHandle()) !== null) {
            $parts[] = t('site: {site}', ['site' => $site], category: 'stethoscope');
        }

        if (($rate = $this->rate()) > 1) {
            $parts[] = t('sampled 1 in {n}', ['n' => $rate], category: 'stethoscope');
        }

        return implode(', ', $parts);
    }

    /** The filter's site handle when it names a real site, else null (all sites). */
    protected function siteHandle(): ?string
    {
        if ($this->site === '') {
            return null;
        }

        try {
            return Sites::getSiteByHandle($this->site, true)?->handle;
        } catch (Throwable) {
            return null;
        }
    }

    protected function sort(): string
    {
        return in_array($this->orderBy, ['count', 'avg', 'max'], true) ? $this->orderBy : 'count';
    }

    protected function rate(): int
    {
        return app(Settings::class)->rate();
    }

    /**
     * Aggregated rows of one entry type, keys decoded into `parts`, limited to the
     * site filter (site = the key part at $siteIndex; null keeps every row).
     *
     * @param  list<string>  $aggregates
     * @return Collection<int, \stdClass>
     */
    protected function rows(string $type, array $aggregates, ?int $siteIndex, ?string $orderBy = null): Collection
    {
        $orderBy ??= in_array($this->sort(), $aggregates, true) ? $this->sort() : $aggregates[0];

        /** @var Collection<int, \stdClass> $rows */
        $rows = $this->aggregate($type, $aggregates, $orderBy, 'desc', 500)
            ->map(fn (object $row): object => (object) [
                'key' => (string) $row->key,
                'parts' => Key::decode((string) $row->key),
                'count' => (float) ($row->count ?? 0),
                'avg' => (float) ($row->avg ?? 0),
                'max' => (float) ($row->max ?? 0),
            ])
            ->filter(fn (object $row): bool => $siteIndex === null || $this->siteHandle() === null || ($row->parts[$siteIndex] ?? null) === $this->siteHandle())
            ->values();

        return $rows;
    }

    /**
     * Counts of one entry type by key, for joining onto another type's rows.
     *
     * @return array<string, float>
     */
    protected function counts(string $type): array
    {
        return $this->aggregate($type, 'count', 'count', 'desc', 1000)
            ->mapWithKeys(fn (object $row): array => [(string) $row->key => (float) ($row->count ?? 0)])
            ->all();
    }

    /** A count scaled back up by the sample rate (prefixed “~” when sampled). */
    protected function scaled(float $count): string
    {
        $rate = $this->rate();

        return $rate > 1 ? '~'.number_format($count * $rate) : number_format($count);
    }

    protected static function ms(float $ms): string
    {
        return number_format($ms).' ms';
    }

    /** @return array<string, mixed> */
    protected static function code(?string $text, ?string $sub = null): array
    {
        return ['text' => $text ?? '—', 'code' => true, 'sub' => $sub];
    }

    /** @return array<string, mixed> */
    protected static function text(?string $text): array
    {
        return ['text' => $text ?? '—'];
    }

    /** @return array<string, mixed> */
    protected static function num(string $text, bool $bold = false): array
    {
        return ['text' => $text, 'numeric' => true, 'bold' => $bold];
    }

    /** @return array{0: string, 1: bool} */
    protected static function header(string $label, bool $numeric = false): array
    {
        return [$label, $numeric];
    }

    protected static function label(?string $type, ?string $group): string
    {
        return $group === null || $group === '' ? (string) $type : "{$type} · {$group}";
    }

    protected static function siteLabel(?string $handle): string
    {
        return $handle ?? t('(none)', category: 'stethoscope');
    }
}
