---
title: Installation
slug: installation
order: 10
summary: Requirements, the Craft 6 beta caution, install, and Pulse's tables.
---

> **Craft 6 only, and still in beta.** Stethoscope is a native **Craft CMS 6** plugin. Craft 6 is
> still in beta, and so is Stethoscope (`6.0.0-beta.1`). Try it on development and staging sites
> first, and **use caution before running it on a production site** — keep backups, and expect
> things to change between betas. It does not run on Craft 5.

## Requirements

- Craft CMS 6.0.0-beta.1 or later
- PHP 8.5
- MySQL, MariaDB or PostgreSQL (Pulse's tables use generated columns)

Stethoscope requires [Laravel Pulse](https://pulse.laravel.com) 1.11, which Composer installs with it
(along with Livewire, which Pulse's dashboard is built on).

## Install

```bash
composer require justinholtweb/craft-stethoscope
php artisan craft:plugin:install stethoscope
```

Or install it from **Settings → Plugins**.

Installing creates Pulse's tables — `pulse_entries`, `pulse_aggregates` and `pulse_values` — with
Pulse's own migration, unless your app already has them (for example because you use Pulse already).
Don't also publish and run Pulse's migration afterwards: the tables already exist.

## Open the dashboard

Click **Stethoscope** in the control panel sidebar, or go to `admin/stethoscope`. Cards fill in as
your site gets traffic; the period selector (1h, 6h, 24h, 7d) works as in Pulse.

## Uninstalling

Uninstalling removes Stethoscope's own entries from Pulse's tables and leaves the tables, and Pulse's
own data, in place: Pulse is a Laravel package and keeps recording until you remove it. To remove it
completely, `composer remove justinholtweb/craft-stethoscope` (which removes Pulse unless your app
requires it) and drop the three `pulse_*` tables. To pause recording without uninstalling, set
`PULSE_ENABLED=false`.
