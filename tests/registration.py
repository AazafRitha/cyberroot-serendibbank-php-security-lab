"""Verify browser registration, duplicate handling, and first sign in."""

import http.cookiejar
import re
import subprocess
import urllib.error
import urllib.parse
import urllib.request
import uuid


BASE = "http://127.0.0.1:8080"
EMAIL = f"registration-{uuid.uuid4().hex[:16]}@example.test"
PASSWORD = "Registration-Passphrase-4832"


def docker(*args):
    return subprocess.run(
        ["docker", "compose", "exec", "-T", "web", *args],
        check=True, capture_output=True, text=True,
    ).stdout.strip()


def visit(opener, path, fields=None):
    body = None if fields is None else urllib.parse.urlencode(fields).encode()
    request = urllib.request.Request(BASE + path, data=body)
    try:
        response = opener.open(request, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    with response:
        return response.status, response.url, response.read().decode()


def csrf(page):
    return re.search(r'name="_csrf" value="([^"]+)"', page).group(1)


opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
try:
    status, _, form = visit(opener, "/register")
    assert status == 200 and "Create your account" in form
    token = csrf(form)

    status, _, _ = visit(opener, "/register", {
        "_csrf": "invalid", "name": "Registration Test", "email": EMAIL,
        "password": PASSWORD, "password_confirmation": PASSWORD,
    })
    assert status == 400

    status, _, form = visit(opener, "/register", {
        "_csrf": token, "name": "Registration Test", "email": EMAIL,
        "password": PASSWORD, "password_confirmation": "different",
    })
    assert status == 422 and "matching password" in form

    status, url, login = visit(opener, "/register", {
        "_csrf": token, "name": "Registration Test", "email": EMAIL,
        "password": PASSWORD, "password_confirmation": PASSWORD,
    })
    assert status == 200 and url.endswith("/login") and "account is ready" in login

    status, _, form = visit(opener, "/register")
    assert status == 200
    status, _, form = visit(opener, "/register", {
        "_csrf": csrf(form), "name": "Registration Test", "email": EMAIL,
        "password": PASSWORD, "password_confirmation": PASSWORD,
    })
    assert status == 409 and "already registered" in form

    status, _, login = visit(opener, "/login")
    status, url, _ = visit(opener, "/login", {
        "_csrf": csrf(login), "email": EMAIL, "password": PASSWORD,
    })
    assert status == 200 and url.endswith("/verify")
    status, _, verify = visit(opener, "/verify")
    code = docker("php", "scripts/read-otp.php", EMAIL)
    status, url, dashboard = visit(opener, "/verify", {"_csrf": csrf(verify), "code": code})
    assert status == 200 and url.endswith("/dashboard")
    assert "Registration Test" in dashboard and "1 accounts" in dashboard
    assert "LKR 0.00" in dashboard
    print("Registration, duplicate handling, and first sign in passed.")
finally:
    docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT customer_id FROM users WHERE email=?"); $s->execute([$argv[1]]); '
        '$id=$s->fetchColumn(); if ($id !== false) { '
        '$p->prepare("DELETE FROM accounts WHERE customer_id=?")->execute([$id]); '
        '$p->prepare("DELETE FROM users WHERE email=?")->execute([$argv[1]]); '
        '$p->prepare("DELETE FROM customers WHERE id=?")->execute([$id]); }',
        "--", EMAIL,
    )
