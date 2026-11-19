---
title: Troubleshooting
slug: troubleshooting
order: 100
summary: Empty cards, 403s, and missing tables.
---

## The dashboard is 403

You need control panel access, **Access Stethoscope** and **View the Stethoscope dashboard**. The
same goes for Pulse's `/pulse`, even locally.

## Cards say “No results”

- Is `PULSE_ENABLED` true?
- Is the card's recorder switched on in the settings?
- Template, query and search cards only count front-end requests; browse the site, not the CP.
- With Redis ingest, is `php artisan pulse:work` running?
- With a high sample rate, quiet sites record little.

## “Base table or view not found: pulse_entries”

Pulse's tables are missing — they're created when Stethoscope is installed. Reinstall the plugin, or
publish and run Pulse's migration: `php artisan vendor:publish --tag=pulse-migrations && php artisan migrate`.

## A card is slow to load

Long periods (7 days) on busy sites aggregate a lot of rows; Pulse caches each card's query for five
seconds. Pulse's Redis ingest and trimming (`PULSE_STORAGE_KEEP`) keep the tables small.

## Unnamed cache blocks show “(unnamed) ?”

The block rendered outside a template Craft rendered (for example inside a plugin's own render).
Give the cache a key: `{% cache using key 'sidebar' %}`.
