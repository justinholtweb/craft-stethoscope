<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use CraftCms\Cms\Search\Events\SearchPerformed;
use CraftCms\Cms\Search\Events\SearchStarting;
use Illuminate\Database\Events\QueryExecuted;
use JustinHoltWeb\Stethoscope\Stethoscope;

/**
 * Front-end `.search()` queries per element type and site: how many, how long,
 * how many found nothing.
 *
 * Craft runs a search two ways. Ordered by score, it calls Search::searchElements()
 * (SearchStarting/SearchPerformed events, with the terms and results). Otherwise
 * the search is a `searchindex` subquery inside the element query, with no
 * event: that query is spotted in Laravel's QueryExecuted, and the element
 * query's ElementsHydrated event that follows says what was found.
 *
 * Search terms only with `recordSearchTerms` on (visitors type them, so they can
 * hold personal data), and only for score-ordered searches; even then they're
 * lower-cased, scrubbed and capped.
 */
class Searches
{
    /** @var list<int> */
    private array $started = [];

    /** Duration (ms) of a filter-style search query waiting for its hydration. */
    private ?int $pending = null;

    public function __construct(private readonly Stethoscope $stethoscope) {}

    public function starting(SearchStarting $event): void
    {
        $this->started[] = hrtime(true);
    }

    public function performed(SearchPerformed $event): void
    {
        $start = array_pop($this->started);

        if ($start === null || $this->stethoscope->isBackgroundRequest() || ! $this->stethoscope->sampled()) {
            return;
        }

        $key = [
            ElementSaves::typeFromClass($event->elementQuery->elementType),
            Stethoscope::siteHandle(),
            $this->stethoscope->settings()->recordSearchTerms ? self::term($event->query->getQuery()) : null,
        ];

        $this->stethoscope->record(Stethoscope::SEARCH, $key, Stethoscope::since($start), ['count', 'avg', 'max']);

        if ($event->results === []) {
            $this->stethoscope->record(Stethoscope::SEARCH_EMPTY, $key);
        }
    }

    public function queryExecuted(QueryExecuted $event): void
    {
        if ($this->started !== [] || ! str_contains($event->sql, 'searchindex') || $this->stethoscope->isBackgroundRequest()) {
            return;
        }

        // A search query that never hydrated (`.count()`, `.ids()`): record it without a type.
        $this->flush(null, false);
        $this->pending = (int) round($event->time);
    }

    public function hydrated(ElementsHydrated $event): void
    {
        if ($this->pending !== null) {
            $first = reset($event->elements);
            $this->flush(is_object($first) ? ElementSaves::type($first) : null, $event->elements === []);
        }
    }

    private function flush(?string $type, bool $empty): void
    {
        if ($this->pending === null) {
            return;
        }

        $ms = $this->pending;
        $this->pending = null;

        if (! $this->stethoscope->sampled()) {
            return;
        }

        $key = [$type, Stethoscope::siteHandle(), null];
        $this->stethoscope->record(Stethoscope::SEARCH, $key, $ms, ['count', 'avg', 'max']);

        if ($empty) {
            $this->stethoscope->record(Stethoscope::SEARCH_EMPTY, $key);
        }
    }

    public static function term(string $term): string
    {
        return mb_substr(mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $term))), 0, 100);
    }
}
