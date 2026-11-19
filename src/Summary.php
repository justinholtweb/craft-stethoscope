<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope;

use Carbon\CarbonInterval;
use Illuminate\Support\Collection;
use JustinHoltWeb\Stethoscope\Support\Key;
use Laravel\Pulse\Facades\Pulse;

/**
 * Stethoscope's numbers as plain arrays, for GraphQL and for other plugins
 * (`app(Summary::class)->for('24_hours', 'en')`). Counts are scaled back up by
 * the sample rate. Per-site figures exist for everything recorded per site;
 * queue jobs and transforms aren't per site, so they're null when a site is given.
 */
class Summary
{
    public const array PERIODS = ['1_hour' => 1, '6_hours' => 6, '24_hours' => 24, '7_days' => 168];

    public function __construct(private readonly Settings $settings) {}

    /**
     * @return array{period: string, site: string|null, sampleRate: int, totals: array<string, int|float|null>, elementSaves: list<array<string, mixed>>}
     */
    public function for(string $period, ?string $site = null): array
    {
        $interval = CarbonInterval::hours(self::PERIODS[$period] ?? 1);
        $rate = $this->settings->rate();

        $rows = fn (string $type, array $aggregates, ?int $siteIndex): Collection => Pulse::aggregate($type, $aggregates, $interval, $aggregates[0], 'desc', 1000)
            ->map(fn (object $row): object => (object) ['parts' => Key::decode((string) $row->key), 'count' => (float) ($row->count ?? 0), 'avg' => (float) ($row->avg ?? 0), 'max' => (float) ($row->max ?? 0)])
            ->filter(fn (object $row): bool => $site === null || $siteIndex === null || ($row->parts[$siteIndex] ?? null) === $site)
            ->values();
        $total = fn (string $type, ?int $siteIndex): ?int => $site !== null && $siteIndex === null ? null : (int) round($rows($type, ['count'], $siteIndex)->sum('count') * $rate);

        $saves = $rows(Stethoscope::ELEMENT_SAVE, ['count', 'avg', 'max'], 2);
        $renders = $rows(Stethoscope::RENDER, ['count', 'avg', 'max'], 1)->filter(fn (object $r): bool => ($r->parts[2] ?? null) === 'page');
        $renderCount = $renders->sum('count');

        return [
            'period' => isset(self::PERIODS[$period]) ? $period : '1_hour',
            'site' => $site,
            'sampleRate' => $rate,
            'totals' => [
                'elementSaves' => (int) round($saves->sum('count') * $rate),
                'elementQueries' => $total(Stethoscope::ELEMENT_QUERY, 2),
                'pageRenders' => (int) round($renderCount * $rate),
                'avgPageRenderMs' => $renderCount > 0 ? round($renders->sum(fn (object $r): float => $r->avg * $r->count) / $renderCount, 1) : null,
                'slowRenders' => $total(Stethoscope::SLOW_RENDER, 1),
                'templateCacheHits' => $total(Stethoscope::CACHE_HIT, 1),
                'templateCacheMisses' => $total(Stethoscope::CACHE_MISS, 1),
                'queueJobs' => $total(Stethoscope::JOB, null),
                'failedJobs' => $total(Stethoscope::JOB_FAILED, null),
                'graphqlQueries' => $total(Stethoscope::GRAPHQL, 2),
                'searches' => $total(Stethoscope::SEARCH, 1),
                'transforms' => $total(Stethoscope::TRANSFORM, null),
            ],
            'elementSaves' => $saves->map(fn (object $r): array => [
                'elementType' => (string) ($r->parts[0] ?? ''),
                'group' => $r->parts[1] ?? null,
                'site' => $r->parts[2] ?? null,
                'drafts' => ($r->parts[3] ?? null) === 'draft',
                'count' => (int) round($r->count * $rate),
                'avgMs' => round($r->avg, 1),
                'maxMs' => (int) $r->max,
            ])->all(),
        ];
    }
}
