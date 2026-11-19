# Stethoscope

Laravel Pulse cards for Craft — element saves and queries, template render times, template caches,
queue jobs, GraphQL, search and image transforms, per section and per site.

> [!WARNING]
> **Craft 6 only, and in beta.** Stethoscope is a native **Craft CMS 6** plugin. Craft 6 is still in beta,
> and so is Stethoscope (`6.0.0-beta.1`). Try it on development and staging sites first and **use caution on
> production**: keep backups, and expect changes between betas. It does not run on Craft 5.

Docs: [justinholt.com/plugins/craft-stethoscope](https://justinholt.com/plugins/craft-stethoscope/docs/). See [PLAN.md](PLAN.md).

## Requirements

- Craft CMS 6.0.0-beta.1 or later (native Craft 6 plugin, no Yii 2 adapter)
- PHP 8.5
- [Laravel Pulse](https://pulse.laravel.com) 1.11 (installed with the plugin; brings Livewire for the dashboard)
- MySQL, MariaDB or PostgreSQL for Pulse's tables (Pulse's own requirement)

## What it shows

Pulse knows Laravel: slow requests, slow queries, jobs, exceptions. Stethoscope adds what only Craft
knows, on one dashboard in the control panel (**Stethoscope** in the sidebar, `admin/stethoscope`):

| Card | What it records |
|---|---|
| Element saves | Saves per element type, section/volume (or Matrix field) and site; live vs draft; average and slowest save time |
| Element queries | How many element queries each front-end page render runs (the N+1 finder), and which element types/sections they load |
| Template renders | Front-end render time per template and site (Twig and Blade), and how many were slow |
| Template caches | `{% cache %}` / `@craftCache` hits, misses and hit rate per cache key and site |
| Queue jobs | Runs, failures, average and slowest duration per job class and queue |
| GraphQL | Queries per schema and operation name, with duration and slow count |
| Searches | Front-end `.search()` queries per element type and site, with how many found nothing |
| Image transforms | Transforms generated per named transform and volume |
| Template timings | Any block you wrap in `{% stethoscope 'label' %}` / `@stethoscope('label')` |

Pulse's own cards (slow queries, queues, exceptions, slow requests, cache, slow outgoing requests) sit
underneath. A **site** filter at the top narrows every Craft card to one site.

## Setup

```bash
composer require justinholtweb/craft-stethoscope
php artisan craft:plugin:install stethoscope
```

Installing creates Pulse's three tables (`pulse_entries`, `pulse_aggregates`, `pulse_values`) if your
app doesn't have them yet. Pulse is configured as in any Laravel app (`config/pulse.php`, `PULSE_*`
env vars). For busy sites, use Pulse's Redis ingest and `php artisan pulse:work`, and set a sample rate.

## Access

The dashboard needs **Access Stethoscope** and **View the Stethoscope dashboard** (Settings → Users).
Admins have both. Pulse's own `/pulse` page uses the same check: Pulse normally lets anyone in when
`APP_ENV=local`; with Stethoscope installed it never does, in any environment.

## Timing part of a template

```twig
{% stethoscope 'related entries' %}
  {% include '_partials/related' %}
{% endstethoscope %}
```

```blade
@stethoscope('related entries')
  @include('partials.related')
@endstethoscope
```

## Privacy

On by default: email and IP addresses are scrubbed out of every recorded key (Stethoscope's and Pulse's,
such as rate-limiter cache keys), query strings are dropped from recorded URLs, Pulse's user cards show
names (never an email-shaped username) without email addresses or Gravatar, and search terms aren't
recorded. Each can be changed in the plugin settings. Stethoscope never records query text, GraphQL
variables, job payloads, element titles or field content.

## GraphQL

Off by default. Turn on **GraphQL summary** in the settings and give a schema **View Stethoscope's
operational summary** to get `stethoscopeSummary(period, site)`: counts and timings, no paths, names
or people. See [docs/graphql.md](docs/graphql.md).

## Development

```bash
composer install
composer test        # Pest
composer phpstan     # Larastan level 6
composer check-cs    # Pint
tests/Harness/check.sh   # live checks against the Craft 6 harness
```

Tested in the Craft 6 harness at `~/Sites/plugin-testing-craft6` (`bin/plugin-wire craft-stethoscope --install`).
