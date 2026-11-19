---
title: Access and permissions
slug: access
order: 40
summary: Who can see the dashboard, and why Pulse's /pulse page is never open, even locally.
---

The dashboard shows operational data — template paths, cache keys, job classes, GraphQL operation
names — so it is behind two permissions (Settings → Users → a user or group):

- **Access Stethoscope** (Craft's own per-plugin permission, which also shows the sidebar link)
- **View the Stethoscope dashboard**

and control panel access. Admins have all of them.

## Pulse's `/pulse` page

Pulse ships its own dashboard at `/pulse`, guarded by a `viewPulse` gate that lets **anyone** in when
`APP_ENV=local`, and nobody otherwise. A local environment flag leaking into production would expose
it. Stethoscope decides `viewPulse` itself, in every environment: a signed-in Craft user with the two
permissions above. Guests always get 403. Every card refresh is checked again, not just the first
page load, so revoking the permission takes effect immediately.

If your app defines its own `viewPulse` gate, Stethoscope's check still applies (it runs first).

## Sidebar link

Settings → Plugins → Stethoscope: **Show in the sidebar**, **Sidebar label** (blank = “Stethoscope”)
and **Sidebar icon** (any Craft icon or custom icon set; blank = Stethoscope's own).
