"""Check that deliberate main-site weaknesses are disabled with APP_LAB_MODE=0."""

import http.cookiejar
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8080"


def browser():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def visit(opener, path, fields=None):
    data = None if fields is None else urllib.parse.urlencode(fields).encode()
    request = urllib.request.Request(BASE + path, data=data)
    try:
        with opener.open(request, timeout=15) as response:
            return response.status, response.url, response.read().decode()
    except urllib.error.HTTPError as error:
        return error.code, error.url, error.read().decode()


def token(html):
    match = re.search(r'name="_csrf" value="([^"]+)"', html)
    assert match, "CSRF token missing"
    return match.group(1)


def cli(*args):
    return subprocess.run(["docker", "compose", "exec", "-T", "web", "php", *args],
                          check=True, capture_output=True, text=True).stdout


def redirect_location(path):
    class NoRedirect(urllib.request.HTTPRedirectHandler):
        def redirect_request(self, request, file_pointer, code, message, headers, new_url):
            return None

    try:
        urllib.request.build_opener(NoRedirect()).open(BASE + path, timeout=15)
    except urllib.error.HTTPError as error:
        assert error.code == 303
        return error.headers.get("Location")
    raise AssertionError("Expected redirect")


for _ in range(30):
    try:
        if visit(browser(), "/")[0] == 200:
            break
    except (urllib.error.URLError, TimeoutError):
        time.sleep(2)
else:
    raise RuntimeError("Core website did not become ready")

created = cli("scripts/create-user.php", "baseline.customer@example.test", "N. Perera", "customer", "1")
password_match = re.search(r"^Password: (.+)$", created, re.MULTILINE)
assert password_match, "Provisioning did not return a password"

session = browser()
_, _, login = visit(session, "/login")
status, _, missing = visit(session, "/login", {"_csrf": token(login), "email": "missing@example.test", "password": "wrong"})
assert status == 401 and "We could not sign you in" in missing
status, _, wrong = visit(session, "/login", {"_csrf": token(login), "email": "baseline.customer@example.test", "password": "wrong"})
assert status == 401 and "We could not sign you in" in wrong

status, url, verify = visit(session, "/login", {
    "_csrf": token(login), "email": "baseline.customer@example.test", "password": password_match.group(1)
})
assert status == 200 and url.endswith("/verify")
assert visit(session, "/dashboard")[1].endswith("/login"), "Portal opened before OTP"
status, _, verify = visit(session, "/verify", {"_csrf": token(verify), "code": "000000"})
assert status == 422 and "could not be accepted" in verify, "Fixed OTP bypass worked in baseline"
code = cli("scripts/read-otp.php", "baseline.customer@example.test").strip()
assert re.fullmatch(r"\d{6}", code)
status, url, dashboard = visit(session, "/verify", {"_csrf": token(verify), "code": code})
assert status == 200 and url.endswith("/dashboard")

assert visit(session, "/statement?account=3")[0] == 404
assert visit(session, "/transfer/repeat?from=1&to=2&amount=1.00")[0] == 404
status, _, branches = visit(session, "/locations/search?q=" + urllib.parse.quote("' OR 1=1 #"))
assert status == 200 and "No branches match" in branches

payload = '<img src=x onerror=alert(1)>'
status, _, offers = visit(session, "/offers/search?q=" + urllib.parse.quote(payload))
assert status == 200 and payload not in offers and "&lt;img" in offers

status, _, application = visit(session, "/apply/account")
assert status == 200
status, _, dashboard = visit(session, "/apply/account", {
    "_csrf": token(application), "product": "Regular savings", "notes": "", "status": "reviewed"
})
assert status == 200 and "Regular savings" in dashboard and "Pending" in dashboard and "Reviewed" not in dashboard

outside = "https://example.org/serendib-check"
assert redirect_location("/digital/continue?next=" + urllib.parse.quote(outside)) == "/login"

print("Baseline mode checks passed")
