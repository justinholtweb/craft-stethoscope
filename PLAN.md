# Stethoscope — plan

Laravel Pulse cards for Craft — element queries, slow templates, cache hit rates and queue depth, per section.

- Package: `justinholtweb/craft-stethoscope` · handle `stethoscope` · namespace `JustinHoltWeb\Stethoscope`
- Editions: single edition · pricing: Free (provisional)
- Laravel integration: laravel/pulse — Pulse v1.11, Laravel 13 compatible (brings Livewire for its dashboard).
- Craft 6 only, native Laravel (no `craftcms/yii2-adapter`, ever). Follow
  `~/Sites/plugin-testing-craft6/porting/COOKBOOK.md` for Craft 6 idioms.

## Why it exists
Pulse shows Laravel-level health (slow requests, queries, jobs) but knows nothing about Craft: which section's
pages are slow, how many element queries a template fires, whether `{% cache %}` is hitting. Joins Microscope
(audit) and Telescope (analytics) as the third "-scope".

## Phases
1. **Recorders.** Element queries per request (by element type/section), slow Twig renders (template path,
   duration), template-cache hits/misses, Craft queue jobs by type and duration, image transforms generated.
2. **Cards.** Livewire Pulse cards for each recorder, with a `craft` dashboard layout publishable to the app.
3. **CP bridge.** Link from Craft's CP to the Pulse dashboard, gated by a Craft permission (Pulse's `viewPulse`
   gate mapped to it).
4. **Microscope/Heat hooks.** Feed recorded numbers into Heat's capacity model and Microscope findings.

## Risks / open questions
- Hook points for Twig render timing in Craft 6 (events vs a Twig extension/profiler).
- Overhead: recorders must be cheap enough to leave on in production with sampling.

## Definition of done (every phase)
`bin/port-check craft-stethoscope` green · Pest suite green · Pint + Larastan level 6 clean · installs, uninstalls and
reinstalls cleanly in the harness · CP pages smoke-tested with `bin/cp-session`.
