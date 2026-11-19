---
title: Configuration
slug: configuration
order: 80
summary: Every setting, config/craft/stethoscope.php, and the Pulse settings that matter.
---

Settings → Plugins → Stethoscope, or `config/craft/stethoscope.php` (which overrides them):

```php
<?php

return [
    'sampleRate' => 1,            // record 1 in N requests/jobs
    'slowTemplateMs' => 250,
    'slowGraphqlMs' => 500,
    'recordElementSaves' => true,
    'recordElementQueries' => true,
    'recordTemplates' => true,
    'recordTemplateCaches' => true,
    'recordQueueJobs' => true,
    'recordGraphql' => true,
    'recordSearches' => true,
    'recordTransforms' => true,
    'recordSearchTerms' => false,
    'redactPersonalData' => true,
    'showUserEmails' => false,
    'enableGraphql' => false,
    'showInCpNav' => true,
    'cpNavLabel' => null,
    'cpNavIcon' => null,
];
```

Recorder switches take effect on the next request.

## Pulse

Pulse's own `config/pulse.php` (publish with `php artisan vendor:publish --tag=pulse-config`) controls:

- `PULSE_ENABLED` — `false` stops all recording, Stethoscope's included.
- storage connection (`PULSE_DB_CONNECTION`) and how long data is kept (`PULSE_STORAGE_KEEP`, 7 days);
- ingest: `storage` writes at the end of each request; `redis` buffers and `php artisan pulse:work`
  writes — better for busy sites;
- Pulse's own recorders and their thresholds and sample rates.

Pulse's dashboard path (`PULSE_PATH`) only moves Pulse's own `/pulse` page; Stethoscope's is always
`admin/stethoscope`.
