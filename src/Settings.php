<?php

declare(strict_types=1);

namespace JustinHoltWeb\Stethoscope;

use CraftCms\Cms\Cp\Icons;
use CraftCms\Cms\Cp\IconSets;
use CraftCms\Cms\Plugin\PluginSettings;

/**
 * Blank-able properties are nullable: Laravel's ConvertEmptyStringsToNull middleware
 * turns an empty field into null before Craft assigns it.
 *
 * Pulse itself (storage, ingest, its own recorders) is configured in `config/pulse.php`.
 */
class Settings extends PluginSettings
{
    /** Record 1 in N requests and queue jobs (1 = every one). Cards scale counts back up. */
    public ?int $sampleRate = 1;

    /** Template renders at least this slow (ms) are also counted as slow. */
    public ?int $slowTemplateMs = 250;

    /** GraphQL queries at least this slow (ms) are also counted as slow. */
    public ?int $slowGraphqlMs = 500;

    /** Element saves, per element type, section/volume and site. */
    public bool $recordElementSaves = true;

    /** Element queries per page render (finds N+1 templates) and per element type/section. */
    public bool $recordElementQueries = true;

    /** Front-end template render times and slow renders. */
    public bool $recordTemplates = true;

    /** `{% cache %}` / `@craftCache` hits and misses. */
    public bool $recordTemplateCaches = true;

    /** Queue jobs by class: count, duration, failures. */
    public bool $recordQueueJobs = true;

    /** GraphQL queries by schema and operation name. */
    public bool $recordGraphql = true;

    /** Front-end `.search()` queries: count, duration, empty results. */
    public bool $recordSearches = true;

    /** Image transforms generated, per transform and volume. */
    public bool $recordTransforms = true;

    /**
     * Also record what people searched for. Off by default: search terms are typed by
     * visitors and can contain personal data.
     */
    public bool $recordSearchTerms = false;

    /**
     * Scrub email addresses and IP addresses out of every Pulse entry's key (Stethoscope's
     * and Pulse's own, e.g. rate-limiter cache keys), and query strings out of URLs.
     */
    public bool $redactPersonalData = true;

    /** Show user email addresses on Pulse's user cards. Off: names (never email-shaped usernames) only. */
    public bool $showUserEmails = false;

    /**
     * Offer the `stethoscopeSummary` GraphQL query (schema component “View Stethoscope's
     * operational summary”). Off by default: it is operational data.
     */
    public bool $enableGraphql = false;

    /** Show a sidebar link to the dashboard (only to people allowed to view it). */
    public bool $showInCpNav = true;

    /** What the sidebar link says. Null uses “Stethoscope”. */
    public ?string $cpNavLabel = null;

    /** A Craft icon name or `set:name` custom icon. Null uses Stethoscope's own icon. */
    public ?string $cpNavIcon = null;

    public function rate(): int
    {
        return max(1, min(1000, (int) $this->sampleRate));
    }

    public function slowTemplateThreshold(): int
    {
        return max(0, (int) ($this->slowTemplateMs ?? 250));
    }

    public function slowGraphqlThreshold(): int
    {
        return max(0, (int) ($this->slowGraphqlMs ?? 500));
    }

    /** The sidebar label, or null for the plugin's name. */
    public function navLabel(): ?string
    {
        $label = trim((string) $this->cpNavLabel);

        return $label === '' ? null : $label;
    }

    /** The sidebar icon if it names a real Craft or icon-set icon, else null. */
    public function navIcon(): ?string
    {
        $icon = trim((string) $this->cpNavIcon);

        return $icon !== '' && self::isIcon($icon) ? $icon : null;
    }

    public static function isIcon(string $icon): bool
    {
        if ($custom = IconSets::parse($icon)) {
            $svg = rescue(fn () => app(IconSets::class)->getIconSetByHandle($custom[0])?->getSvg($custom[1]), null, false);

            return is_string($svg) && stripos($svg, '<svg') !== false;
        }

        return (bool) preg_match('/^[\da-z\-]+$/D', $icon) && is_file(Icons::resolveIconPath($icon));
    }

    /**
     * @return array<string, list<string>>
     */
    public function getRules(): array
    {
        return [
            'sampleRate' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'slowTemplateMs' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'slowGraphqlMs' => ['nullable', 'integer', 'min:0', 'max:600000'],
            'cpNavLabel' => ['nullable', 'string', 'max:100'],
            'cpNavIcon' => ['nullable', 'string', 'max:100', 'regex:/^([a-zA-Z][\w\-]*:)?[\w\-]+$/'],
        ];
    }
}
