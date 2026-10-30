"""Exercise device listing, revocation, and the remembered-device bypass."""

import http.cookiejar
import os
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid


BASE = os.environ.get("APP_BASE_URL", "http://127.0.0.1:8080")
HOST = os.environ.get("MANAGEMENT_HOST", "manage.localhost")


def docker(*args):
    return subprocess.run(
        ["docker", "compose", "exec", "-T", "web", *args],
        check=True, capture_output=True, text=True,
    ).stdout.strip()


def visit(opener, path, fields=None, management=False, extra_headers=None):
    body = None if fields is None else urllib.parse.urlencode(fields).encode()
    headers = {"Host": HOST} if management else {}
    headers.update(extra_headers or {})
    request = urllib.request.Request(BASE + path, data=body, headers=headers)
    try:
        with opener.open(request, timeout=20) as response:
            return response.status, response.url, response.read().decode()
    except urllib.error.HTTPError as error:
        return error.code, error.url, error.read().decode()


def csrf(page):
    return re.search(r'name="_csrf" value="([^"]+)"', page).group(1)


def new_user(role, customer_id=None):
    email = f"session-{role}-{uuid.uuid4().hex[:12]}@example.test"
    args = ["php", "scripts/create-user.php", email, "Session Tester", role]
    if customer_id is not None:
        args.append(str(customer_id))
    output = docker(*args)
    password = re.search(r"^Password: (.+)$", output, re.MULTILINE).group(1)
    return email, password


def sign_in(email, password, management=False, remember=False):
    jar = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(jar))
    status, _, page = visit(opener, "/login", management=management)
    assert status == 200
    status, url, _ = visit(opener, "/login", {
        "_csrf": csrf(page), "email": email, "password": password,
        "remember_device": "1" if remember else "0",
    }, management=management)
    assert status == 200 and url.endswith("/verify")
    _, _, page = visit(opener, "/verify", management=management)
    code = docker("php", "scripts/read-otp.php", email)
    status, url, page = visit(opener, "/verify", {
        "_csrf": csrf(page), "code": code,
    }, management=management)
    assert status == 200 and (url.endswith("/") if management else url.endswith("/dashboard"))
    session_cookie = next(cookie.value for cookie in jar if cookie.name == "serendib_session")
    return opener, jar, session_cookie


def record_id(session_cookie):
    return int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM user_sessions WHERE session_hash=?"); '
        '$s->execute([hash("sha256", $argv[1])]); echo $s->fetchColumn();',
        "--", session_cookie,
    ))


created = []
try:
    email, password = new_user("customer", 1)
    created.append(email)
    first, _, _ = sign_in(email, password)
    second, _, second_cookie = sign_in(email, password)
    forged, _, forged_cookie = sign_in(email, password)
    remembered, remembered_jar, remembered_cookie = sign_in(email, password, remember=True)
    assert any(c.name == "sb_device_trust" and c.value == "1" for c in remembered_jar)

    status, _, page = visit(first, "/sessions")
    assert status == 200 and "Signed-in devices" in page
    assert page.count('class="session-card"') >= 4
    for session_cookie in [second_cookie, forged_cookie, remembered_cookie]:
        status, _, page = visit(first, "/sessions/revoke", {
            "_csrf": csrf(page), "action": "revoke", "id": str(record_id(session_cookie)),
        })
        assert status == 200 and "Session ended" in page

    status, url, _ = visit(second, "/sessions")
    assert status == 200 and url.endswith("/login")
    status, url, page = visit(forged, "/sessions", extra_headers={
        "Cookie": f"serendib_session={forged_cookie}; sb_device_trust=1",
    })
    assert status == 200 and url.endswith("/sessions") and "Current device" in page
    status, url, page = visit(remembered, "/sessions")
    assert status == 200 and url.endswith("/sessions") and "Current device" in page
    assert "Ended" in page

    staff_email, staff_password = new_user("staff")
    created.append(staff_email)
    staff, _, _ = sign_in(staff_email, staff_password, management=True)
    status, url, page = visit(staff, "/sessions", management=True)
    assert status == 200 and url.endswith("/sessions") and "Recent sessions" in page
    print("Session listing, revocation, remembered-device bypass, and management access passed.")
finally:
    for email in created:
        docker(
            "php", "-r",
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$s=$p->prepare("DELETE FROM users WHERE email=?"); $s->execute([$argv[1]]);',
            "--", email,
        )
