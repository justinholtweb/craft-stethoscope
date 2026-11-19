---
title: The dashboard
slug: dashboard
order: 20
summary: Each Craft card, what it measures, the site filter, sampling, and customising the layout.
---

The dashboard is a Laravel Pulse page with Craft's cards first and Pulse's own below. Every card
refreshes every few seconds; each can be sorted by count, average or slowest where it times things.

## Craft cards

**Element saves** — saves per element type and section or volume (nested entries show their Matrix
field as `field:<handle>`), per site, split into live saves and draft saves. Revisions are skipped:
they're created alongside a save. Average and slowest save time come from Craft's `ElementSaving` and
`ElementSaved` events.

**Element queries** — two tables. *Per page render*: for each front-end page template, how many
element queries one render runs, on average and at most. A listing page that runs 1 + N queries
stands out here; add `.with()` eager loading and watch the number fall. *By element type*: which
element types and sections those queries loaded. Counted from Craft's `ElementsHydrated` event, so
queries that hydrate elements (`.all()`, `.one()`, loops, eager loading) count; `.count()`, `.ids()`
and `.exists()` don't.

**Template renders** — render time per front-end template and site, Twig and Blade alike. Page
templates are timed from start to finish; other renders through Craft (element partials,
`renderTemplate`) are marked *partial*. `{% include %}` happens inside a render and isn't timed on its
own: wrap it in a [timing tag](templates). *Slow* counts renders at or over the slow threshold
(250 ms by default).

**Template caches** — `{% cache %}` and `@craftCache` hits and misses per cache key and site, with
the hit rate. Numbers in keys are collapsed (`nav-42` and `nav-43` are one `nav-*` row); unnamed cache
blocks are shown by their template. A miss is only counted when template caching is on for the request.

**Queue jobs** — runs, failures, average and slowest duration per job class and queue. Only the class
name is recorded, never the job's description or payload.

**GraphQL** — queries per schema and operation name (unnamed operations are “(anonymous)”), per site,
with duration and a slow count (500 ms by default). Never the query text or variables.

**Searches** — front-end `.search()` queries per element type and site: how many, how long, and how
many found nothing. Search terms are off by default (see [Privacy](privacy)).

**Image transforms** — transforms generated, per named transform (or “custom”) and volume.

**Template timings** — blocks you've wrapped in `{% stethoscope %}` or `@stethoscope`, by label, site
and template.

Control panel pages, the dashboard's own refreshes and console commands aren't counted as site
renders, queries or searches. Element saves, queue jobs, GraphQL and transforms are counted wherever
they happen.

## Site filter

With more than one site, a **Site** menu sits next to the period selector. Choosing a site narrows
every Craft card to it (`?site=<handle>`). Queue jobs and transforms aren't per site and stay as they
are. An unknown handle is ignored.

## Sampling

On busy sites set **Sample rate** to record 1 in N requests and queue jobs. The decision is made once
per request, so a page's queries and its render time are recorded together. Cards multiply counts back
up and mark them with “~”, and say “sampled 1 in N”.

## Customising the layout

```bash
php artisan vendor:publish --tag=stethoscope-dashboard
```

copies the dashboard to `resources/views/vendor/stethoscope/dashboard.blade.php`, where it takes over.
Rearrange, resize (`cols`, `rows`) or remove cards, or add your own Pulse cards. The Craft cards are
`stethoscope.element-saves`, `stethoscope.element-queries`, `stethoscope.templates`,
`stethoscope.template-caches`, `stethoscope.queue-jobs`, `stethoscope.graphql`, `stethoscope.searches`,
`stethoscope.transforms` and `stethoscope.timings`; you can also add them to Pulse's own dashboard.
