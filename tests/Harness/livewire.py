"""Loads every lazy Livewire card on a dashboard page the way the browser does
(POST /livewire-*/update with __lazyLoad), using a curl cookie jar.

Usage: python3 -I livewire.py <cookie-jar> <dashboard-url> [other-cookie-jar]
With a second jar, the card updates are replayed as that (other) session, using
its XSRF-TOKEN cookie, to prove each refresh re-checks the permission.
Prints one line per card: `<component name> <http status> <base64 of the card html>`.
"""
import base64, time, html, http.cookiejar, json, re, ssl, sys, urllib.parse, urllib.request

jar_path, url = sys.argv[1], sys.argv[2]
jar = http.cookiejar.MozillaCookieJar(jar_path)
jar.load(ignore_discard=True, ignore_expires=True)
ctx = ssl.create_default_context(); ctx.check_hostname = False; ctx.verify_mode = ssl.CERT_NONE
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar), urllib.request.HTTPSHandler(context=ctx))

try:
    page = opener.open(url).read().decode()
except urllib.error.HTTPError as e:
    print(f"page {e.code}"); sys.exit()
config = json.loads(re.search(r'livewireScriptConfig = (\{.*?\});', page).group(1))
tags = re.findall(r'<[^>]*wire:snapshot="([^"]*)"[^>]*x-intersect="\$wire\.__lazyLoad\(&#039;([^&]*)&#039;\)"', page)
headers = {"Content-Type": "application/json", "X-Livewire": "1", "Accept": "application/json"}
if len(sys.argv) > 3:
    jar2 = http.cookiejar.MozillaCookieJar(sys.argv[3]); jar2.load(ignore_discard=True, ignore_expires=True)
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar2), urllib.request.HTTPSHandler(context=ctx))
    headers["X-XSRF-TOKEN"] = urllib.parse.unquote(next(c.value for c in jar2 if c.name == "XSRF-TOKEN"))
    config["csrf"] = ""
for snapshot, payload in tags:
    snapshot = html.unescape(snapshot)
    name = json.loads(snapshot)["memo"]["name"]
    body = json.dumps({"_token": config["csrf"], "components": [{"snapshot": snapshot, "updates": {}, "calls": [{"path": "", "method": "__lazyLoad", "params": [payload]}]}]}).encode()
    req = urllib.request.Request(config["uri"], data=body, method="POST", headers=headers)
    for attempt in range(6):
        try:
            res = opener.open(req); status = res.status; data = json.loads(res.read().decode())
            out = data["components"][0]["effects"].get("html", "")
        except urllib.error.HTTPError as e:
            status, out = e.code, ""
        if status not in (429, 500, 502, 503):  # shared harness: rate limits and transient restarts
            break
        time.sleep(2 * (attempt + 1))
    print(name, status, base64.b64encode(out.encode()).decode())
