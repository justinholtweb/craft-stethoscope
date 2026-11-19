<?php

// Stethoscope harness fixtures (idempotent). Everything is prefixed `stethoscope`; cleanup.php removes it.
//   entry type + section stethoscopeLog (channel, primary site), entries stethoscope-log-1..5
//   users stethoscopeviewer (CP + Access Stethoscope + View the dashboard), stethoscopeeditor (CP + Access Stethoscope only),
//         both with password `stethoscope-check-1` (local harness only)
// Run with: ddev artisan tinker --execute "require 'storage/stethoscope-fixtures.php';"

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\TitleField;
use CraftCms\Cms\Section\Data\Section;
use CraftCms\Cms\Section\Data\SectionSiteSettings;
use CraftCms\Cms\Section\Enums\SectionType;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserPermissions;

$fail = fn (string $what, $model) => throw new RuntimeException($what.': '.json_encode(method_exists($model, 'errors') ? $model->errors()->all() : $model));

$type = EntryTypes::getEntryTypeByHandle('stethoscopeLog');
if (! $type) {
    $type = new EntryType(['name' => 'Stethoscope Log', 'handle' => 'stethoscopeLog']);
    $type->setFieldLayout(FieldLayout::createFromConfig(['type' => Entry::class, 'tabs' => [['name' => 'Content', 'elements' => [['type' => TitleField::class]]]]]));
    EntryTypes::saveEntryType($type) || $fail('entry type', $type);
    $type = EntryTypes::getEntryTypeByHandle('stethoscopeLog');
}

$section = Sections::getSectionByHandle('stethoscopeLog');
if (! $section) {
    $section = new Section(['name' => 'Stethoscope Log', 'handle' => 'stethoscopeLog', 'type' => SectionType::Channel]);
    $s = new SectionSiteSettings;
    $s->siteId = Sites::getPrimarySite()->id;
    $s->hasUrls = true;
    $s->uriFormat = 'stethoscope-log/{slug}';
    $s->template = 'st/entry';
    $s->enabledByDefault = true;
    $section->setSiteSettings([$s->siteId => $s]);
    $section->setEntryTypes([$type]);
    Sections::saveSection($section) || $fail('section', $section);
    $section = Sections::getSectionByHandle('stethoscopeLog');
}

$ids = [];
foreach (range(1, 5) as $n) {
    $entry = Entry::find()->section('stethoscopeLog')->slug("stethoscope-log-{$n}")->status(null)->one();
    if (! $entry) {
        $entry = new Entry;
        $entry->sectionId = $section->id;
        $entry->typeId = $type->id;
        $entry->slug = "stethoscope-log-{$n}";
        $entry->title = "Heartbeat {$n}";
        Elements::saveElement($entry) || $fail("entry {$n}", $entry);
    }
    $ids[] = $entry->id;
}

$users = [];
foreach (['stethoscopeviewer' => ['accessCp', 'accessPlugin-stethoscope', 'stethoscope:viewDashboard'], 'stethoscopeeditor' => ['accessCp', 'accessPlugin-stethoscope']] as $username => $permissions) {
    $user = User::find()->username($username)->status(null)->one() ?? new User;
    $user->username = $username;
    $user->email = "{$username}@stethoscope.test";
    $user->fullName = ucfirst(substr($username, 11)).' Stethoscope';
    $user->newPassword = 'stethoscope-check-1';
    $user->active = true;
    $user->pending = false;
    Elements::saveElement($user) || $fail("user {$username}", $user);
    app(UserPermissions::class)->saveUserPermissions($user->id, $permissions) || $fail("permissions {$username}", $permissions);
    $users[$username] = $user->id;
}

echo json_encode(['section' => $section->id, 'entries' => $ids, 'users' => $users]);
