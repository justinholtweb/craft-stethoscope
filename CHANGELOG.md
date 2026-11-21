# Release Notes for Stethoscope

## 6.0.0-beta.2 - 2026-11-21

### Changed
- The docs FAQ now compares Stethoscope with Control Tower and Microscope.

## 6.0.0-beta.1 - 2026-11-19

> {warning} Craft 6 is in beta, and so is Stethoscope. Use caution on production sites.

### Added
- Laravel Pulse dashboard in the control panel (`admin/stethoscope`) with nine Craft cards: element saves, element queries per page render, template renders, template caches, queue jobs, GraphQL, searches, image transforms and template timings — plus Pulse's own cards.
- Per-site breakdowns on every Craft card, and a site filter for the whole dashboard.
- `{% stethoscope 'label' %}…{% endstethoscope %}` (Twig) and `@stethoscope('label')…@endstethoscope` (Blade) to time any part of a template.
- “View the Stethoscope dashboard” permission. Pulse's `viewPulse` gate maps to it in every environment, so Pulse's own `/pulse` page is never open to guests, even with `APP_ENV=local`.
- Privacy defaults: email/IP addresses scrubbed from every Pulse key, query strings dropped from recorded URLs, user cards without emails or Gravatar, search terms off.
- Sample rate (1 in N requests/jobs); cards scale counts back up and mark them “~”.
- `stethoscopeSummary` GraphQL query, off by default, behind its own schema component, and limited to the sites the schema can read.
- `vendor:publish --tag=stethoscope-dashboard` to customise the dashboard layout.
- Sidebar link with an optional custom label and icon.
- Install creates Pulse's tables when they're missing; uninstall removes only Stethoscope's entries.
