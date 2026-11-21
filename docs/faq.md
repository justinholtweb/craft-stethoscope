---
title: FAQ
slug: faq
order: 90
summary: Craft 6 and the beta, overhead, Pulse, and what gets recorded.
---

## Does Stethoscope work on Craft 5?

No. It is a native **Craft 6** plugin built on Laravel Pulse. Craft 6 is still in beta.

## Is it safe to use in production?

Use caution. Craft 6 and Stethoscope (`6.0.0-beta.1`) are both in beta. Test on staging, keep
backups, and start production with a sample rate and Pulse's Redis ingest.

## Is it free?

Yes.

## How much overhead does it add?

Recorders do a little arithmetic per event and hand Pulse an entry; Pulse writes them in one batch at
the end of the request (or from `pulse:work` with Redis ingest). On busy sites, raise the sample rate
and switch off cards you don't need.

## I already use Pulse. Does Stethoscope get in the way?

No. It uses your Pulse tables and config, and adds its cards. Two things change: `viewPulse` is
decided by the Craft permission (see [Access](access)), and personal data is scrubbed from all Pulse
keys (see [Privacy](privacy)) — both can be relaxed in settings except the access check.

## Why does the element queries card ignore `.count()`?

It counts queries that hydrate elements. `.count()`, `.ids()` and `.exists()` return numbers, not
elements, and Craft fires no event for them.

## Why isn't my `{% include %}` in the template renders card?

Includes run inside the page's render. Wrap them in `{% stethoscope 'label' %}` — see
[Timing templates](templates).

## Does it work with Blade templates?

Yes. Renders, element queries, template caches and searches are recorded the same for Twig and Blade
pages, and `@stethoscope` mirrors `{% stethoscope %}`.

## Does it work with GraphQL?

The GraphQL card records your API's queries. A `stethoscopeSummary` query exists but is off by
default — see [GraphQL](graphql).

## Stethoscope or Control Tower?

[Control Tower](https://craft-controltower.com) is an operations dashboard for Craft 5: live
traffic, who's editing, content health, the queue, server metrics and alert rules that post to Slack,
Teams or email.

Stethoscope is about **performance**, on Craft 6. Its cards sit in Laravel Pulse and show element
saves, element queries per page template, slow templates, template cache hit rates, jobs, GraphQL
and search. Both show queue activity. Stethoscope has no alert rules; Pulse is a dashboard you look
at, not a pager.

## And Microscope?

[Microscope](https://craft-microscope.com) runs an **audit**: it checks your schema, templates, PHP,
database and server once and tells you what to fix. Stethoscope **watches** real traffic all the
time. Use Microscope to find the problems, and Stethoscope to see whether the fixes held.
