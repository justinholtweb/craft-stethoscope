#!/bin/bash
# Live checks against the Craft 6 harness (~/Sites/plugin-testing-craft6), shared with other agents.
# Fixtures (all `stethoscope`-prefixed, removed at the end): section stethoscopeLog + 5 entries,
# users stethoscopeviewer (permission) / stethoscopeeditor (CP only), GraphQL schemas/tokens, and
# when STETHOSCOPE_MULTISITE=1 (default) a second site `stethoscopefr`, added/removed under bin/harness-lock.
# Templates: tests/Harness/templates/* → resources/views/st/. Pulse data is read straight from
# pulse_entries, counting only rows written after a marker, with Stethoscope-specific keys.
set -uo pipefail
exec < /dev/null
H=/Users/jholt/Sites/plugin-testing-craft6
SRC=$(cd "$(dirname "$0")" && pwd)
U=https://plugin-testing-craft6.ddev.site
cd "$H" || exit 2
mkdir -p resources/views/st
cp "$SRC"/templates/* resources/views/st/
cp "$SRC"/jobs/*.php app/Jobs/
for f in fixtures gql-fixtures cleanup multisite-fixtures multisite-cleanup; do cp "$SRC/$f.php" "storage/stethoscope-$f.php"; done
pass=0 fail=0
ok() { if [ "$2" = "$3" ]; then echo "✓ $1"; pass=$((pass+1)); else echo "✗ $1 (got '$2', want '$3')"; fail=$((fail+1)); fi; }
tk() { ddev artisan tinker --execute "$1" 2>/dev/null | grep -v -e WARNING -e DEPRECATED -e '^$' | tail -1; }
# Project-config writes can hit Busy/StaleResource from concurrent writers: retry in a fresh process.
tkr() { local out; for i in 1 2 3 4 5; do out=$(tk "$1"); [ -n "$out" ] && ! echo "$out" | grep -qiE 'busy|stale|exception|error' && { echo "$out"; return; }; sleep 2; done; echo "$out"; }
# docker exec (not ddev exec): no extra shell layer eating the backslashes in JSON keys
sql() { docker exec ddev-plugin-testing-craft6-db mysql -udb -pdb -N -B db -e "$1" 2>/dev/null | tail -1; }
settings() { tkr "\$p=CraftCms\\Cms\\Support\\Facades\\Plugins::getPlugin('stethoscope'); echo CraftCms\\Cms\\Support\\Facades\\Plugins::savePluginSettings(\$p, $1) ? 'saved' : 'failed';" >/dev/null; sleep 2; }
mark() { sql "select coalesce(max(id),0) from pulse_entries"; }
# n TYPE KEY-JSON [since]: entries of that type and exact key written after the marker
n() { sleep 1; sql "select count(*) from pulse_entries e where e.id > ${3:-$M} and e.type = '$1' and e.key = '$2'"; }
v() { sql "select coalesce(max(e.value),'') from pulse_entries e where e.id > ${3:-$M} and e.type = '$1' and e.key = '$2'"; }
get() { curl -sk -o /dev/null -w '%{http_code}' "$@"; }
# login <username> <password> → cookie jar (CP login is rate-limited per IP on the shared harness: retry 429)
login() { local j x code; j=$(mktemp); for i in 1 2 3 4 5 6; do
  curl -sk -c "$j" -o /dev/null "$U/admin/login"
  x=$(awk '$6=="XSRF-TOKEN"{print $7}' "$j" | python3 -I -c 'import sys,urllib.parse;print(urllib.parse.unquote(sys.stdin.read().strip()))')
  code=$(curl -sk -b "$j" -c "$j" -o /dev/null -w '%{http_code}' -H 'Accept: application/json' -H "X-XSRF-TOKEN: $x" -X POST "$U/actions/users/login" --data-urlencode "loginName=$1" --data-urlencode "password=$2")
  [ "$code" = 200 ] && break; sleep $((i * 3)); done; echo "$j"; }
# cards <jar> <url> [replay-jar] → "name status" lines; cardhtml <jar> <url> <name> → that card's HTML
cards() { python3 -I "$SRC/livewire.py" "$@" | awk '{print $1, $2}'; }
cardhtml() { python3 -I "$SRC/livewire.py" "$1" "$2" | awk -v n="$3" '$1==n {print $3}' | base64 -d 2>/dev/null; }
# cardtext: the card's visible text, tags stripped and whitespace collapsed, one cell per line
cardtext() { cardhtml "$@" | python3 -I -c 'import html,re,sys; t=re.sub(r"<[^>]+>", "\n", sys.stdin.read()); print("\n".join(l.strip() for l in html.unescape(t).splitlines() if l.strip()))'; }
has() { grep -qxF -- "$1" && echo yes || echo no; }
# gq <schema> <query> <python expr over r/d/s> [operationName]  (GraphQL is rate-limited too: retry 429)
gq() { local tok body res; tok=$(printf 'stethoscope%s' "$1" | awk '{ s=$0; while (length(s) < 32) s=s "0"; print s }')
  body=$(python3 -I -c 'import json,sys; d={"query": sys.argv[1]}; d.update({"operationName": sys.argv[2]} if sys.argv[2] else {}); print(json.dumps(d))' "$2" "${4:-}")
  for i in 1 2 3 4 5; do res=$(curl -sk -w '\n%{http_code}' -H "Authorization: Bearer $tok" -H 'Content-Type: application/json' --data "$body" "$U/actions/graphql/api")
    [ "$(echo "$res" | tail -1)" != 429 ] && break; sleep $((i * 3)); done
  echo "$res" | sed '$d' | python3 -I -c 'import json,sys
try: r=json.load(sys.stdin)
except Exception: print("not json"); sys.exit()
if r.get("errors"): print("ERR:" + "; ".join(e.get("message","") for e in r["errors"])); sys.exit()
d=r.get("data") or {}; s=d.get("stethoscopeSummary") or {}; j=lambda *v: "/".join(str(x) for x in v)
print(eval(sys.argv[1]))' "$3"; }

# ---- Setup ----
settings "['sampleRate' => 1, 'slowTemplateMs' => 250, 'slowGraphqlMs' => 500, 'recordSearchTerms' => false, 'redactPersonalData' => true, 'showUserEmails' => false, 'enableGraphql' => false, 'showInCpNav' => true, 'cpNavLabel' => null, 'cpNavIcon' => null]"
FIX=$(tkr "require 'storage/stethoscope-fixtures.php';")
VIEWER=$(echo "$FIX" | grep -oE '"stethoscopeviewer":[0-9]+' | grep -oE '[0-9]+$')
ENTRY2=$(echo "$FIX" | grep -oE '"entries":\[[0-9,]+' | grep -oE '[0-9]+' | sed -n 2p)
ok "fixtures created" "$([ -n "$VIEWER" ] && [ -n "$ENTRY2" ] && echo yes)" "yes"
MULTI=no
if [ "${STETHOSCOPE_MULTISITE:-1}" = 1 ]; then
  "$H/bin/harness-lock" ddev artisan tinker --execute "require 'storage/stethoscope-multisite-fixtures.php';" < /dev/null >/dev/null 2>&1
  [ "$(tk "echo CraftCms\\Cms\\Support\\Facades\\Sites::getSiteByHandle('stethoscopefr', true) ? 'yes' : 'no';")" = yes ] && MULTI=yes
fi
tkr "require 'storage/stethoscope-gql-fixtures.php';" >/dev/null

# ---- Access: Pulse's local-env gate never applies ----
ok "anonymous: /pulse refused even though APP_ENV=local" "$(get "$U/pulse")" "403"
ok "anonymous: dashboard redirects to login" "$(get "$U/admin/stethoscope")" "302"
ADMIN=$("$H/bin/cp-session")
VJ=$(login stethoscopeviewer stethoscope-check-1)
EJ=$(login stethoscopeeditor stethoscope-check-1)
ok "admin: dashboard loads" "$(get -b "$ADMIN" "$U/admin/stethoscope")" "200"
ok "viewer (permission): dashboard loads" "$(get -b "$VJ" "$U/admin/stethoscope")" "200"
ok "editor (CP + Access Stethoscope, no view permission): dashboard refused" "$(get -b "$EJ" "$U/admin/stethoscope")" "403"
ok "editor: Pulse's own /pulse refused too" "$(get -b "$EJ" "$U/pulse")" "403"
ok "viewer: Pulse's own /pulse allowed (same permission)" "$(get -b "$VJ" "$U/pulse")" "200"
ok "gate: viewer may view, editor may not" "$(tk "echo json_encode([Illuminate\\Support\\Facades\\Gate::forUser(App\\Models\\User::find($VIEWER))->allows('viewPulse'), Illuminate\\Support\\Facades\\Gate::forUser(App\\Models\\User::where('username','stethoscopeeditor')->first())->allows('viewPulse')]);")" "[true,false]"
ok "dashboard is noindex and loads no external fonts" "$(curl -sk -b "$ADMIN" "$U/admin/stethoscope" | grep -c 'noindex')/$(curl -sk -b "$ADMIN" "$U/admin/stethoscope" | grep -c 'fonts.bunny.net')" "1/0"
ok "CP sidebar: viewer sees the Stethoscope link" "$(curl -sk -b "$VJ" "$U/admin/dashboard" | grep -cE 'admin\\/stethoscope["?]')" "1"
ok "CP sidebar: editor doesn't" "$(curl -sk -b "$EJ" "$U/admin/dashboard" | grep -cE 'admin\\/stethoscope["?]')" "0"

# ---- Recording: templates, element queries, caches, timings (Twig and Blade) ----
M=$(mark)
for p in page page-blade page page-blade; do get "$U/st/$p" >/dev/null; done
for e in page page-blade; do
  ok "$e: page render recorded (2 renders)" "$(n stethoscope_render "[\"st/$e\",\"default\",\"page\"]")" "2"
  ok "$e: element queries per render = 6 (1 listing + 5 N+1)" "$(n stethoscope_page_queries "[\"st/$e\",\"default\"]")/$(v stethoscope_page_queries "[\"st/$e\",\"default\"]")" "2/6"
  ok "$e: {% stethoscope %} / @stethoscope timing recorded with its template" "$(n stethoscope_timing "[\"st-loop\",\"default\",\"st/$e\"]")" "2"
done
ok "element queries by type/section (24 = 4 renders × 6)" "$(n stethoscope_element_query '["entry","stethoscopeLog","default"]')" "24"
ok "template cache: named key grouped (st-nav-42 → st-nav-*), hits + misses = 4" "$(( $(n stethoscope_cache_hit '["st-nav-*","default"]') + $(n stethoscope_cache_miss '["st-nav-*","default"]') ))" "4"
ok "template cache: second render of each engine is a hit" "$(n stethoscope_cache_hit '["st-nav-*","default"]' | awk '{print ($1 >= 2) ? "yes" : "no"}')" "yes"
settings "['slowTemplateMs' => 0]"
M=$(mark); get "$U/st/page" >/dev/null
ok "slow threshold 0: every render is also slow" "$(n stethoscope_slow_render '["st/page","default","page"]')" "1"
settings "['slowTemplateMs' => 250]"
M=$(mark); get "$U/st/page" >/dev/null
ok "slow threshold 250: a fast render isn't slow" "$(n stethoscope_slow_render '["st/page","default","page"]')" "0"
M=$(mark); get -b "$ADMIN" "$U/admin/dashboard" >/dev/null
# (Other agents' front-end traffic is recorded too, so look for CP templates, not for "nothing".)
ok "CP pages aren't recorded as site renders" "$(sql "select count(*) from pulse_entries e where e.id > $M and e.type in ('stethoscope_render','stethoscope_page_queries') and (e.key like '%dashboard%' or e.key like '%craft::%' or e.key like '%_layouts/cp%')")" "0"

# ---- Searches ----
M=$(mark)
get "$U/st/page?q=heartbeat" >/dev/null; get "$U/st/page-blade?q=zzzz" >/dev/null
get "$U/st/page?qs=heartbeat" >/dev/null; get "$U/st/page-blade?qs=zzzz" >/dev/null
ok "search (filter style, Twig): recorded per element type and site" "$(n stethoscope_search '["entry","default",null]')" "3"
ok "search (filter style, no results): counted as empty" "$(n stethoscope_search_empty '[null,"default",null]')" "1"
ok "search (score-ordered, no results): counted as empty" "$(n stethoscope_search_empty '["entry","default",null]')" "1"
ok "search terms not recorded by default" "$(sql "select count(*) from pulse_entries e where e.id > $M and e.key like '%heartbeat%'")" "0"
settings "['recordSearchTerms' => true]"
M=$(mark); get "$U/st/page?qs=heartbeat" >/dev/null; get "$U/st/page?qs=ada%40example.com" >/dev/null
ok "recordSearchTerms on: score-ordered term recorded" "$(n stethoscope_search '["entry","default","heartbeat"]')" "1"
ok "recordSearchTerms on: an email typed as a search is scrubbed" "$(n stethoscope_search '["entry","default","[email]"]')/$(sql "select count(*) from pulse_entries e where e.id > $M and e.key like '%example.com%'")" "1/0"
settings "['recordSearchTerms' => false]"

# ---- Element saves ----
M=$(mark)
entry() { tkr "\$e=CraftCms\\Cms\\Entry\\Elements\\Entry::find()->id($ENTRY2)->site('${2:-default}')->status(null)->one(); $1 echo CraftCms\\Cms\\Support\\Facades\\Elements::saveElement(\$e) ? 'saved' : 'failed';"; }
ok "save an entry" "$(entry '$e->title = "Heartbeat 2b";')" "saved"
ok "element save recorded per type, section, site (live)" "$(n stethoscope_element_save '["entry","stethoscopeLog","default","live"]')" "1"
ok "save duration recorded" "$(v stethoscope_element_save '["entry","stethoscopeLog","default","live"]' | grep -cE '^[0-9]+$')" "1"
tkr "\$e=CraftCms\\Cms\\Entry\\Elements\\Entry::find()->id($ENTRY2)->one(); \$d=app(CraftCms\\Cms\\Element\\Drafts::class)->createDraft(\$e, $VIEWER, 'stethoscope draft'); \$d->title='Draft beat'; echo CraftCms\\Cms\\Support\\Facades\\Elements::saveElement(\$d) ? 'saved' : 'failed';" >/dev/null
ok "draft saves counted separately" "$(sql "select count(*) from pulse_entries e where e.id > $M and e.type='stethoscope_element_save' and e.key='[\"entry\",\"stethoscopeLog\",\"default\",\"draft\"]'" | awk '{print ($1 >= 1) ? "yes" : "no"}')" "yes"
entry '$e->title = "Heartbeat 2";' >/dev/null

# ---- Queue jobs ----
M=$(mark)
tk "App\\Jobs\\StethoscopeOk::dispatch()->onQueue('stethoscope'); App\\Jobs\\StethoscopeFail::dispatch()->onQueue('stethoscope'); echo 1;" >/dev/null
ddev artisan queue:work --queue=stethoscope --stop-when-empty --tries=1 >/dev/null 2>&1
ok "queue jobs: runs recorded by class and queue" "$(n stethoscope_job '["App\\\\Jobs\\\\StethoscopeOk","stethoscope"]')/$(n stethoscope_job '["App\\\\Jobs\\\\StethoscopeFail","stethoscope"]')" "1/1"
ok "queue jobs: only the failing job counted as failed" "$(n stethoscope_job_failed '["App\\\\Jobs\\\\StethoscopeOk","stethoscope"]')/$(n stethoscope_job_failed '["App\\\\Jobs\\\\StethoscopeFail","stethoscope"]')" "0/1"
tk "Illuminate\\Support\\Facades\\DB::table('failed_jobs')->where('queue','stethoscope')->delete(); echo 1;" >/dev/null

# ---- GraphQL ----
ok "gql: summary query absent while enableGraphql is off" "$(gq full '{ stethoscopeSummary { period } }' '0')" 'ERR:Cannot query field "stethoscopeSummary" on type "Query".'
M=$(mark)
gq full 'query StethoscopeCheck { entries(section: "stethoscopeLog") { id } }' 'len(d["entries"])' StethoscopeCheck >/dev/null
ok "gql: queries recorded by schema and operation name" "$(n stethoscope_graphql '["stethoscope_full","StethoscopeCheck","default"]')" "1"
ok "gql: never the query text" "$(sql "select count(*) from pulse_entries e where e.id > $M and e.key like '%stethoscopeLog%' and e.type like 'stethoscope_graphql%'")" "0"
settings "['enableGraphql' => true]"
tk 'CraftCms\Cms\Support\Facades\Gql::invalidateCaches(); echo 1;' >/dev/null
ok "gql: summary for a schema with the component" "$(gq full '{ stethoscopeSummary(period: "1_hour") { period sampleRate totals { elementSaves pageRenders queueJobs } } }' 'j(s["period"], s["sampleRate"], s["totals"]["elementSaves"] > 0, s["totals"]["pageRenders"] > 0, s["totals"]["queueJobs"] >= 2)')" "1_hour/1/True/True/True"
ok "gql: element saves per section and site" "$(gq full '{ stethoscopeSummary { elementSaves { elementType group site drafts count } } }' 'any(r["group"] == "stethoscopeLog" and r["site"] == "default" and not r["drafts"] and r["count"] >= 1 for r in s["elementSaves"])')" "True"
ok "gql: no template paths or names in the summary" "$(gq full '{ stethoscopeSummary { totals { pageRenders } elementSaves { group } } }' '"st/page" in str(r)')" "False"
ok "gql: schema without the component has no summary query" "$(gq none '{ stethoscopeSummary { period } }' '0')" 'ERR:Cannot query field "stethoscopeSummary" on type "Query".'
ok "gql: unknown period is an error" "$(gq full '{ stethoscopeSummary(period: "forever") { period } }' '0' | cut -c1-30)" "ERR:Unknown period “forever”. "
ok "gql: unknown site reads like a forbidden one" "$(gq full '{ stethoscopeSummary(site: "nope") { period } }' '0')" "ERR:This schema can’t read the “nope” site."
ok "gql: site-less totals are null when a site is asked for" "$(gq full '{ stethoscopeSummary(site: "default") { site totals { queueJobs transforms elementSaves } } }' 'j(s["site"], s["totals"]["queueJobs"], s["totals"]["transforms"], s["totals"]["elementSaves"] is not None)')" "default/None/None/True"
ok "gql: results are live, not cached for long (new save shows)" "$(a=$(gq full '{ stethoscopeSummary { totals { elementSaves } } }' 's["totals"]["elementSaves"]'); entry '$e->title = "Heartbeat 2c";' >/dev/null; sleep 6; b=$(gq full '{ stethoscopeSummary { totals { elementSaves } } }' 's["totals"]["elementSaves"]'); [ "$b" -gt "$a" ] && echo grew)" "grew"
entry '$e->title = "Heartbeat 2";' >/dev/null
settings "['enableGraphql' => false]"
tk 'CraftCms\Cms\Support\Facades\Gql::invalidateCaches(); echo 1;' >/dev/null
ok "gql: turning it off removes the query again" "$(gq full '{ stethoscopeSummary { period } }' '0' | cut -c1-24)" "ERR:Cannot query field \""

# ---- Privacy ----
M=$(mark)
tk "Illuminate\\Support\\Facades\\Cache::get('stethoscope-probe:ada@example.com:10.1.2.3'); echo 1;" >/dev/null
ok "Pulse's own cache card: email and IP scrubbed from keys" "$(n cache_miss 'stethoscope-probe:[email]:[ip]')/$(sql "select count(*) from pulse_entries e where e.id > $M and e.key like '%ada@example%'")" "1/0"
tk "config(['pulse.recorders.'.Laravel\\Pulse\\Recorders\\SlowOutgoingRequests::class.'.threshold' => 0]); rescue(fn () => Illuminate\\Support\\Facades\\Http::timeout(5)->get('http://localhost/stethoscope-probe?token=s3cret&email=ada%40example.com')); echo 1;" >/dev/null
ok "Pulse's outgoing requests: query strings dropped" "$(n slow_outgoing_request '["GET","http:\\/\\/localhost\\/stethoscope-probe"]')/$(sql "select count(*) from pulse_entries e where e.id > $M and e.key like '%s3cret%'")" "1/0"
get "$U/st/timing-email" >/dev/null
ok "timing labels scrubbed too" "$(n stethoscope_timing '["st-label [email]","default","st/timing-email"]')" "1"
ok "user cards: name, no email, no Gravatar" "$(tk "echo json_encode(app(Laravel\\Pulse\\Pulse::class)->resolveUsers(collect([$VIEWER]))->find($VIEWER));")" "{\"name\":\"Viewer Stethoscope\",\"extra\":\"#$VIEWER\",\"avatar\":null}"
settings "['showUserEmails' => true]"
ok "user cards: email only when showUserEmails is on" "$(tk "echo app(Laravel\\Pulse\\Pulse::class)->resolveUsers(collect([$VIEWER]))->find($VIEWER)->extra;")" "stethoscopeviewer@stethoscope.test"
settings "['showUserEmails' => false, 'redactPersonalData' => false]"
M=$(mark); tk "Illuminate\\Support\\Facades\\Cache::get('stethoscope-probe:raw@example.com'); echo 1;" >/dev/null
ok "redaction off: keys stored as-is (opt-in)" "$(n cache_miss 'stethoscope-probe:raw@example.com')" "1"
tk "Illuminate\\Support\\Facades\\DB::table('pulse_entries')->where('key','like','stethoscope-probe%')->delete(); Illuminate\\Support\\Facades\\DB::table('pulse_aggregates')->where('key','like','stethoscope-probe%')->delete(); echo 1;" >/dev/null
settings "['redactPersonalData' => true]"

# ---- Dashboard cards (Livewire) ----
CARDS=$(cards "$ADMIN" "$U/admin/stethoscope")
ok "admin: all 15 cards load (9 Craft + 6 Pulse)" "$(echo "$CARDS" | grep -c ' 200$')/$(echo "$CARDS" | wc -l | tr -d ' ')" "15/15"
ok "viewer: cards load" "$(cards "$VJ" "$U/admin/stethoscope" | grep -c ' 200$')" "15"
ok "editor replaying the viewer's cards: every refresh refused" "$(cards "$VJ" "$U/admin/stethoscope" "$EJ" | grep -c ' 403$')" "15"
ok "card: element saves show section and site" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.element-saves | has 'entry · stethoscopeLog')" "yes"
ok "card: templates list st/page" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.templates | has 'st/page')" "yes"
ok "card: element queries show the N+1 page" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.element-queries | has 'st/page-blade')" "yes"
ok "card: template caches" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.template-caches | has 'st-nav-*')" "yes"
ok "card: queue jobs" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.queue-jobs | has 'App\Jobs\StethoscopeFail')" "yes"
ok "card: graphql" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.graphql | has 'StethoscopeCheck')" "yes"
ok "card: timings" "$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.timings | has 'st-loop')" "yes"
ok "unknown ?site= is ignored, not an error" "$(cards "$ADMIN" "$U/admin/stethoscope?site=nope" | grep -c ' 200$')" "15"
settings "['sampleRate' => 10]"
ST=$(cardtext "$ADMIN" "$U/admin/stethoscope" stethoscope.element-saves)
ok "sampled: cards say so and mark counts with ~" "$(echo "$ST" | grep -c 'sampled 1 in 10' | awk '{print ($1 > 0)}')/$(echo "$ST" | grep -cE '^~[0-9]' | awk '{print ($1 > 0)}')" "1/1"
settings "['sampleRate' => 1]"

# ---- Multisite ----
if [ "$MULTI" = yes ]; then
  M=$(mark)
  ok "multisite: save on stethoscopefr" "$(entry '$e->title = "Battement 2";' stethoscopefr)" "saved"
  ok "multisite: save recorded against the French site" "$(n stethoscope_element_save '["entry","stethoscopeLog","stethoscopefr","live"]')" "1"
  ok "multisite: French page renders" "$(get "$U/stethoscopefr/st/page")" "200"
  ok "multisite: page queries recorded per site" "$(n stethoscope_page_queries '["st/page","stethoscopefr"]')" "1"
  ok "multisite: element queries per site" "$(n stethoscope_element_query '["entry","stethoscopeLog","stethoscopefr"]')" "6"
  ok "multisite: French entry page (translated title)" "$(curl -sk "$U/stethoscopefr/journal/stethoscope-log-1" | grep -o '<h1>[^<]*')" "<h1>Battement 1"
  FR=$(cardtext "$ADMIN" "$U/admin/stethoscope?site=stethoscopefr" stethoscope.element-saves)
  ok "multisite card: ?site=stethoscopefr shows the French row" "$(echo "$FR" | has stethoscopefr)" "yes"
  ok "multisite card: … and no default-site rows" "$(echo "$FR" | has default)" "no"
  ok "multisite card: the site selector lists the French site" "$(curl -sk -b "$ADMIN" "$U/admin/stethoscope" | grep -c 'Stethoscope Français')" "1"
  settings "['enableGraphql' => true]"; tk 'CraftCms\Cms\Support\Facades\Gql::invalidateCaches(); echo 1;' >/dev/null
  ok "multisite gql: site filter returns only that site's saves" "$(gq full '{ stethoscopeSummary(site: "stethoscopefr") { elementSaves { site } } }' 'j(len(s["elementSaves"]) > 0, all(r["site"] == "stethoscopefr" for r in s["elementSaves"]))')" "True/True"
  settings "['enableGraphql' => false]"
  entry '$e->title = "Heartbeat 2";' stethoscopefr >/dev/null
fi

# ---- Uninstall purges only Stethoscope's entries; reinstall works ----
if [ "${STETHOSCOPE_REINSTALL:-1}" = 1 ]; then
  ddev artisan craft:plugin:uninstall stethoscope -n >/dev/null 2>&1
  ok "uninstall: Stethoscope's entries purged, Pulse's tables and data kept" "$(sql "select count(*) from pulse_entries e where e.type like 'stethoscope\\_%'")/$(sql "select count(*) from information_schema.tables where table_schema='db' and table_name like 'pulse\\_%'")/$(sql "select count(*) from pulse_entries e where e.type = 'cache_miss'" | awk '{print ($1 > 0)}')" "0/3/1"
  ddev artisan craft:plugin:install stethoscope -n >/dev/null 2>&1
  ok "reinstall: plugin installed, tables reused" "$(tk "echo CraftCms\\Cms\\Support\\Facades\\Plugins::isPluginInstalled('stethoscope') ? 'yes' : 'no';")" "yes"
  ok "after reinstall: anonymous /pulse still refused" "$(get "$U/pulse")" "403"
fi

# ---- Cleanup ----
tkr "require 'storage/stethoscope-cleanup.php';" >/dev/null
if [ "$MULTI" = yes ]; then
  for i in 1 2 3; do
    "$H/bin/harness-lock" ddev artisan tinker --execute "require 'storage/stethoscope-multisite-cleanup.php';" < /dev/null >/dev/null 2>&1
    [ "$(tk "echo CraftCms\\Cms\\Support\\Facades\\Sites::getSiteByHandle('stethoscopefr', true) ? 'yes' : 'no';")" = no ] && break; sleep 3
  done
fi
settings "['sampleRate' => 1, 'enableGraphql' => false, 'recordSearchTerms' => false, 'redactPersonalData' => true, 'showUserEmails' => false]"
ok "cleanup: fixtures gone" "$(tk "echo (CraftCms\\Cms\\Support\\Facades\\Sections::getSectionByHandle('stethoscopeLog') ? 1 : 0) + (CraftCms\\Cms\\Support\\Facades\\Sites::getSiteByHandle('stethoscopefr', true) ? 1 : 0);")" "0"
rm -f "$ADMIN" "$VJ" "$EJ"

echo "--- $pass passed, $fail failed"
[ "$fail" -eq 0 ]
