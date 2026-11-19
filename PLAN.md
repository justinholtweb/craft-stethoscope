# Stethoscope — plan

Laravel Pulse cards for Craft — element saves and queries, template render times, template caches, queue
jobs, GraphQL, search and image transforms, per section and per site.

- Package: `justinholtweb/craft-stethoscope` · handle `stethoscope` · namespace `JustinHoltWeb\Stethoscope`
- Editions: single edition · pricing: Free
- Laravel integration: laravel/pulse ^1.11 (brings Livewire 4 for its dashboard). Core Craft 6 beta.1 does not
  use or ship Pulse (checked `vendor/craftcms/cms`), so Stethoscope owns installing it.
- Craft 6 only, native Laravel (no `craftcms/yii2-adapter`, ever). Follow
  `~/Sites/plugin-testing-craft6/porting/COOKBOOK.md` for Craft 6 idioms.

## Why it exists
Pulse shows Laravel-level health (slow requests, queries, jobs) but knows nothing about Craft: which section's
pages are slow, how many element queries a template fires, whether `{% cache %}` is hitting. Joins Microscope
(audit) and Telescope (analytics) as the third "-scope".

## Status (2026-11-18): 6.0.0-beta.1 ready for review

| Phase | Status |
|---|---|
| 1. Recorders | ✅ element saves (type/section/site, live vs draft, duration), element queries (per page render + per type/section), template renders (page/partial, slow), template cache hits/misses (subclassed `TemplateCaches`), queue jobs (class/queue, duration, failures), GraphQL (schema/operation/site, slow), searches (both Craft search paths, empty results), image transforms, `{% stethoscope %}`/`@stethoscope` timings. Per-request sampling. |
| 2. Cards | ✅ nine Livewire Pulse cards sharing one view, site filter, sort; `stethoscope::dashboard` publishable (`--tag=stethoscope-dashboard`) |
| 3. CP bridge | ✅ dashboard at `admin/stethoscope` (CP route + Pulse's Authorize middleware, re-checked on every Livewire refresh); `viewPulse` decided by a Gate::before → Access Stethoscope + View dashboard, any environment; sidebar label/icon settings |
| 4. Microscope/Heat hooks | ⏸ Microscope and Heat are Craft 5 plugins with no Craft 6 ports yet. The hook point exists: `app(JustinHoltWeb\Stethoscope\Summary::class)->for($period, $site)` returns the numbers as arrays. Wire it when those ports exist. |
| GraphQL | ✅ `stethoscopeSummary`, off by default (setting) + schema component `stethoscope.summary:read`; counts only |
| Multisite + translations | ✅ site in every Craft key, site filter, GraphQL site arg validated against allowed sites; all strings via `t(…, category: 'stethoscope')`, `lang/en/stethoscope.php` |
| Twig + Blade | ✅ `{% stethoscope %}` / `@stethoscope` share Timings; all recorders hook TemplateManager events that fire for both engines; check.sh asserts parity |
| Privacy | ✅ Pulse filter scrubs emails/IPs from every key (Pulse's too) and query strings from URLs; user cards without email/Gravatar; search terms off |
| Icon, docs, promos, site | icon ✅, README/docs/CHANGELOG ✅; promos and justinholt.com page — see below |

Checks: Pest 59 (unit), `tests/Harness/check.sh` (live, see file header). Pint, Larastan 6, port-check green.

## Design notes
- Pulse's tables belong to Pulse: install runs Pulse's own migration only when they're missing; uninstall purges
  Stethoscope's entry types only and leaves the tables (Pulse keeps recording until the package is removed).
- Pulse's `viewPulse` default is "anyone when APP_ENV=local". Stethoscope's `Gate::before` decides it in every
  environment, so `/pulse` is never open; it runs before any app-defined gate.
- Element queries are counted from `ElementsHydrated` (no event exists for an executed element query in beta.1);
  `.count()/.ids()/.exists()` aren't counted. Filter-style searches have no event either: spotted as a
  `searchindex` SQL in `QueryExecuted`, typed by the following hydration.
- Twig `{% include %}` isn't a TemplateManager render, hence the timing tag.

## Risks / open questions
- Overhead on very busy sites: use sampling + Pulse Redis ingest. Not load-tested.
- `TemplateCaches` is replaced by a subclass only when nothing else bound it; a core change to its public
  method signature would need a release.
- Image transform recording is untested end-to-end in the harness (no transform was generated during checks);
  `Transforms::name()` is unit-tested and the listener is the same shape as the others.

## Questions for Justin
1. **GraphQL:** `stethoscopeSummary` exists but is off by default (setting + schema component), counts only — no
   template paths, cache keys, job classes or operation names. Keep it that way, drop it, or expose more?
2. **Pulse's `/pulse` page:** it stays reachable (behind the same Craft permission). Prefer to hide it entirely so
   `admin/stethoscope` is the only dashboard?
3. **Uninstall:** keeps Pulse's tables (and Pulse's own data) and purges only Stethoscope's entries. OK, or should
   uninstall drop the tables when Stethoscope created them?
4. **Permissions:** the dashboard needs Craft's "Access Stethoscope" plus "View the Stethoscope dashboard". One
   permission would be simpler, but Craft only shows the sidebar link with "Access Stethoscope". Fine as is?
5. **Phase 4:** wire Microscope/Heat hooks once they're ported to Craft 6 — still wanted?
6. **Search terms:** recorded only when switched on, and only for `orderBy('score')` searches (Craft fires no
   event with the terms otherwise). Good enough, or worth parsing them out of the search SQL?

## Definition of done (every phase)
`bin/port-check craft-stethoscope` green · Pest suite green · Pint + Larastan level 6 clean · installs, uninstalls and
reinstalls cleanly in the harness · CP pages smoke-tested with `bin/cp-session`.
