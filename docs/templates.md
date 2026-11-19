---
title: Timing templates
slug: templates
order: 30
summary: The {% stethoscope %} tag and @stethoscope directive for timing any part of a template.
---

Craft times whole renders. To find out which part of a page is slow — an include, a loop, a macro —
wrap it:

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

Both record to the **Template timings** card under the label, the site and the template it's in, with
count, average and slowest time. They call the same code, so a Twig and a Blade version of a page
record identically.

- Labels can be any expression: `{% stethoscope 'card ' ~ entry.type.handle %}`. Keep them to a
  small set of values — each distinct label is its own row — and don't put titles or user data in them.
  Email and IP addresses in labels are scrubbed anyway.
- Timings nest, and an exception inside the block still ends the timing.
- An empty label times nothing.
- The tag works in site templates. Front-end requests only: control panel renders aren't recorded.
