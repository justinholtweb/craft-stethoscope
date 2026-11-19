<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope;

use CraftCms\Cms\Support\Facades\Sites;
use JustinHoltWeb\Stethoscope\Support\Key;
use Laravel\Pulse\Pulse;
use Throwable;

/**
 * The one place Stethoscope's recorders write through: per-request sampling,
 * key encoding + redaction, and the entry types the cards read.
 *
 * Scoped: the sampling decision is made once per request (or queue job), so a
 * page's element queries and render time are recorded together or not at all.
 */
class Stethoscope
{
    public const string ELEMENT_SAVE = 'stethoscope_element_save';

    public const string ELEMENT_QUERY = 'stethoscope_element_query';

    public const string PAGE_QUERIES = 'stethoscope_page_queries';

    public const string RENDER = 'stethoscope_render';

    public const string SLOW_RENDER = 'stethoscope_slow_render';

    public const string CACHE_HIT = 'stethoscope_cache_hit';

    public const string CACHE_MISS = 'stethoscope_cache_miss';

    public const string JOB = 'stethoscope_job';

    public const string JOB_FAILED = 'stethoscope_job_failed';

    public const string GRAPHQL = 'stethoscope_graphql';

    public const string SLOW_GRAPHQL = 'stethoscope_slow_graphql';

    public const string SEARCH = 'stethoscope_search';

    public const string SEARCH_EMPTY = 'stethoscope_search_empty';

    public const string TRANSFORM = 'stethoscope_transform';

    public const string TIMING = 'stethoscope_timing';

    /** Every entry type Stethoscope writes (purged on uninstall). */
    public const array TYPES = [
        self::ELEMENT_SAVE, self::ELEMENT_QUERY, self::PAGE_QUERIES, self::RENDER, self::SLOW_RENDER,
        self::CACHE_HIT, self::CACHE_MISS, self::JOB, self::JOB_FAILED, self::GRAPHQL, self::SLOW_GRAPHQL,
        self::SEARCH, self::SEARCH_EMPTY, self::TRANSFORM, self::TIMING,
    ];

    private ?bool $sampled = null;

    public function __construct(private readonly Settings $settings) {}

    public function settings(): Settings
    {
        return $this->settings;
    }

    /** Whether this request/job is in the sample. Decided once. */
    public function sampled(): bool
    {
        if ($this->sampled === null) {
            $rate = $this->settings->rate();
            $this->sampled = $rate === 1 || random_int(1, $rate) === 1;
        }

        return $this->sampled;
    }

    /** @internal Tests and the harness force a decision. */
    public function forceSample(?bool $sampled): void
    {
        $this->sampled = $sampled;
    }

    /**
     * Requests whose templates, queries and searches aren't the site's own: control
     * panel pages, the dashboard's Livewire polling, and console commands.
     */
    public function isBackgroundRequest(): bool
    {
        if (app()->runningInConsole()) {
            return true;
        }

        try {
            $request = request();

            return $request->isCpRequest() || str_contains($request->path(), 'livewire');
        } catch (Throwable) {
            return true;
        }
    }

    /**
     * Records one entry. Each key part is trimmed, length-capped and (unless switched
     * off) scrubbed of email and IP addresses.
     *
     * @param  list<string|int|null>  $key
     * @param  list<'count'|'max'|'avg'|'sum'|'min'>  $aggregates
     */
    public function record(string $type, array $key, ?int $value = null, array $aggregates = ['count']): void
    {
        try {
            $entry = app(Pulse::class)->record($type, Key::encode($key, $this->settings->redactPersonalData), $value);

            foreach ($aggregates as $aggregate) {
                $entry->{$aggregate}();
            }
        } catch (Throwable) {
            // Monitoring must never break the page it's measuring.
        }
    }

    public static function siteHandle(?int $siteId = null): ?string
    {
        try {
            return $siteId === null
                ? Sites::getCurrentSite()->handle
                : Sites::getSiteById($siteId, true)?->handle;
        } catch (Throwable) {
            return null;
        }
    }

    /** A template path as shown on cards: Craft renders `''` as the site's index. */
    public static function templateLabel(string $template): string
    {
        return $template === '' ? 'index' : $template;
    }

    /** Milliseconds since an `hrtime(true)` reading. */
    public static function since(int $start): int
    {
        return (int) round((hrtime(true) - $start) / 1_000_000);
    }
}
