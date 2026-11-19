<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope\Recorders;

use CraftCms\Cms\Element\Queries\Events\ElementsHydrated;
use CraftCms\Cms\View\Events\PageTemplateRendered;
use CraftCms\Cms\View\TemplateMode;
use JustinHoltWeb\Stethoscope\Stethoscope;

/**
 * Element queries on front-end requests: how many each page template fires
 * (the N+1 finder), and which element types/sections they load.
 *
 * Counted from Craft's `ElementsHydrated` event, which fires once per executed
 * element query that hydrates elements (`.all()`, `.one()`, loops, eager
 * loading). `.count()`, `.ids()` and `.exists()` don't hydrate, so they aren't
 * counted.
 */
class ElementQueries
{
    private int $count = 0;

    public function __construct(private readonly Stethoscope $stethoscope) {}

    public function hydrated(ElementsHydrated $event): void
    {
        if ($this->stethoscope->isBackgroundRequest() || ! $this->stethoscope->sampled()) {
            return;
        }

        $this->count++;
        $first = reset($event->elements);

        $this->stethoscope->record(Stethoscope::ELEMENT_QUERY, [
            is_object($first) ? ElementSaves::type($first) : null,
            is_object($first) ? ElementSaves::group($first) : null,
            Stethoscope::siteHandle(),
        ]);
    }

    public function pageRendered(PageTemplateRendered $event): void
    {
        if ($event->templateMode !== TemplateMode::Site || $this->stethoscope->isBackgroundRequest() || ! $this->stethoscope->sampled()) {
            return;
        }

        $this->stethoscope->record(Stethoscope::PAGE_QUERIES, [
            Stethoscope::templateLabel($event->template),
            Stethoscope::siteHandle(),
        ], $this->count, ['count', 'avg', 'max']);

        $this->count = 0;
    }
}
