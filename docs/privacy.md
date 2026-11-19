---
title: Privacy
slug: privacy
order: 50
summary: What is and isn't recorded, and the settings that scrub personal data from every Pulse entry.
---

Monitoring data is kept for days (Pulse keeps 7 by default) and read by developers, so Stethoscope
keeps personal data out of it.

## Never recorded

Element titles and field content, user names and emails (except as described for user cards below),
search terms (unless you switch them on), SQL bindings, GraphQL query text and variables, job
descriptions and payloads.

## On by default

**Scrub personal data** rewrites every Pulse entry's key before it is stored — Stethoscope's and
Pulse's own. Email addresses become `[email]` and IPv4/IPv6 addresses `[ip]`, so a rate-limiter
cache key like `login|ada@example.com|10.0.0.1` is recorded as `login|[email]|[ip]`. Query strings,
fragments and `user:password@` are dropped from recorded URLs (Pulse's slow requests and slow
outgoing requests), so API keys in URLs don't end up in your monitoring tables.

**User cards** (Pulse's application usage card) show a user's full name, or their username if it isn't
an email address, and their user ID — no email address, and no Gravatar image (a Gravatar URL is a
hash of the email address, and loading it tells Gravatar who is looking at your dashboard).

## Off by default

- **Record search terms** — visitors type these, so they can contain names, emails or anything else.
  When on, terms are lower-cased, scrubbed and capped at 100 characters, and recorded for searches
  ordered by score.
- **Show user emails** — on Pulse's user cards.

Turning **Scrub personal data** off stores keys as-is. Only do that if you've checked what your app's
cache keys and outgoing URLs contain.
