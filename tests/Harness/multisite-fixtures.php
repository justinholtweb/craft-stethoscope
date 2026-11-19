<?php

// Stethoscope harness: a second site, `stethoscopefr` (French, /stethoscopefr/), owned by Stethoscope's
// checks (other agents add and remove their own sites; never touch theirs). The stethoscopeLog section
// gets French URIs and the first entry a French title. Idempotent; remove with multisite-cleanup.php.
// Run with: ddev artisan tinker --execute "require 'storage/stethoscope-multisite-fixtures.php';"

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Site\Data\Site;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;

$fr = Sites::getSiteByHandle('stethoscopefr', true);

if (! $fr) {
    $primary = Sites::getPrimarySite();
    $fr = new Site;
    $fr->groupId = $primary->groupId;
    $fr->name = 'Stethoscope Français';
    $fr->handle = 'stethoscopefr';
    $fr->language = 'fr-FR';
    $fr->hasUrls = true;
    // Absolute: an `@web/…` base URL never matched requests in this harness.
    $fr->baseUrl = rtrim((string) config('app.url'), '/').'/stethoscopefr/';
    $fr->enabled = true;
    Sites::saveSite($fr) || throw new RuntimeException('site: '.json_encode($fr->errors()->all()));
}

$section = Sections::getSectionByHandle('stethoscopeLog');
$settings = $section->getSiteSettings();

if (! isset($settings[$fr->id])) {
    $s = new SectionSiteSettings;
    $s->siteId = $fr->id;
    $s->hasUrls = true;
    $s->uriFormat = 'journal/{slug}';
    $s->template = 'st/entry';
    $s->enabledByDefault = true;
    $settings[$fr->id] = $s;
    $section->setSiteSettings($settings);
    Sections::saveSection($section) || throw new RuntimeException('section: '.json_encode($section->errors()->all()));
}

foreach (Entry::find()->section('stethoscopeLog')->status(null)->all() as $e) {
    if (! Entry::find()->id($e->id)->site('stethoscopefr')->status(null)->exists()) {
        Elements::saveElement($e);
    }
}

$entry = Entry::find()->section('stethoscopeLog')->slug('stethoscope-log-1')->site('stethoscopefr')->status(null)->one()
    ?? throw new RuntimeException('stethoscope-log-1 did not propagate to stethoscopefr');
$entry->title = 'Battement 1';
Elements::saveElement($entry) || throw new RuntimeException('entry: '.json_encode($entry->errors()->all()));

echo json_encode(['site' => $fr->id, 'entry' => $entry->id]);
