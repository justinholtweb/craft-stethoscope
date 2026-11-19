<?php

// Removes everything fixtures.php and gql-fixtures.php created (all `stethoscope`-prefixed).
// The stethoscopefr site goes separately (multisite-cleanup.php, under bin/harness-lock).
// Run with: ddev artisan tinker --execute "require 'storage/stethoscope-cleanup.php';"

use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;

foreach (Entry::find()->section('stethoscopeLog')->status(null)->site('*')->unique()->drafts(null)->all() as $e) {
    Elements::deleteElement($e, true);
}
if ($s = Sections::getSectionByHandle('stethoscopeLog')) {
    Sections::deleteSection($s);
}
if ($t = EntryTypes::getEntryTypeByHandle('stethoscopeLog')) {
    EntryTypes::deleteEntryType($t);
}
foreach (['stethoscopeviewer', 'stethoscopeeditor'] as $username) {
    if ($u = User::find()->username($username)->status(null)->one()) {
        Elements::deleteElement($u, true);
    }
}
foreach (Gql::getTokens() as $t) {
    if (str_starts_with((string) $t->name, 'stethoscope_')) {
        Gql::deleteTokenById($t->id);
    }
}
foreach (Gql::getSchemas() as $s) {
    if (str_starts_with((string) $s->name, 'stethoscope_')) {
        Gql::deleteSchemaById($s->id);
    }
}
Gql::invalidateCaches();
echo 'cleaned';
