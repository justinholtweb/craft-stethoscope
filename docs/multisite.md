---
title: Multi-site
slug: multisite
order: 70
summary: Per-site breakdowns, the site filter, and what isn't per site.
---

Every Craft card records the site:

- element saves: the site the element was saved in (each site of a propagated save is its own save);
- element queries, template renders, template caches, searches, timings: the site of the front-end request;
- GraphQL: the site of the request.

Use the **Site** menu at the top of the dashboard, or `stethoscopeSummary(site:)` over GraphQL, to see
one site. Queue jobs and image transforms aren't tied to a site and are always shown in full.

Site handles are checked against your sites before use; an unknown handle in the URL is ignored, and
over GraphQL it is an error.

Labels on the dashboard are translated through Craft's translation system (`lang/<locale>/stethoscope.php`);
Pulse's own card labels are in English.
