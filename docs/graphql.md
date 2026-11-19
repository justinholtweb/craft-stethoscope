---
title: GraphQL
slug: graphql
order: 60
summary: The stethoscopeSummary query, why it's off by default, and how it's scoped.
---

Stethoscope's numbers are operational data, so the GraphQL query is **off by default**. To turn it on:

1. Settings → Plugins → Stethoscope → **GraphQL summary**.
2. In a GraphQL schema, tick **Stethoscope → View Stethoscope's operational summary**. Give it only
   to schemas used by trusted tools (a status board, an uptime check), never a public schema.

User-authenticated GraphQL requests get it only with the dashboard permission.

```graphql
{
  stethoscopeSummary(period: "24_hours", site: "en") {
    period
    site
    sampleRate
    totals {
      elementSaves elementQueries pageRenders avgPageRenderMs slowRenders
      templateCacheHits templateCacheMisses queueJobs failedJobs graphqlQueries searches transforms
    }
    elementSaves { elementType group site drafts count avgMs maxMs }
  }
}
```

- `period`: `1_hour` (default), `6_hours`, `24_hours` or `7_days`. Anything else is an error.
- `site`: a site handle the schema can read. Omit it for every site the schema can read: a schema
  limited to some sites only ever sees those sites' rows and totals, and — because queue jobs,
  failed jobs and transforms aren't recorded per site — gets `null` for those unless it can read
  every site. An unknown site and a site the schema
  can't read give the same error, so a schema can't discover site handles.
- Totals that aren't recorded per site (queue jobs, failed jobs, transforms) are `null` when you ask
  for a site.
- Counts are scaled by the sample rate.
- The summary holds counts and timings only — no template paths, cache keys, job classes, operation
  names or people. Those stay on the dashboard.
- Craft caches GraphQL responses; this one for at most five seconds.
