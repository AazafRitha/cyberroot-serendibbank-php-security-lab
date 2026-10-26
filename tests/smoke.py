"""Check basic customer and staff workflows on a fresh local Compose stack."""

import http.cookiejar
import json
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
    with opener.open(urllib.request.Request(BASE + path, data=body), timeout=15) as response:
        return response.url, response.read().decode()


def csrf(page):
    match = re.search(r'name="_csrf" value="([^"]+)"', page)
    assert match is not None
    return match.group(1)


def sign_in(email, name, role, customer_id=None):
    args = ["php", "scripts/create-user.php", email, name, role]
    if customer_id is not None:
        args.append(str(customer_id))
    output = docker(*args)
    created.append(email)
    password = re.search(r"^Password: (.+)$", output, re.MULTILINE).group(1)
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    _, page = visit(opener, "/login")
    url, _ = visit(opener, "/login", {"_csrf": csrf(page), "email": email, "password": password})
    assert url.endswith("/verify")
    _, page = visit(opener, "/verify")
    code = docker("php", "scripts/read-otp.php", email)
    url, page = visit(opener, "/verify", {"_csrf": csrf(page), "code": code})
    return opener, url, page


customer_email = f"smoke-customer-{uuid.uuid4().hex[:12]}@example.test"
staff_email = f"smoke-staff-{uuid.uuid4().hex[:12]}@example.test"
created = []
try:
    customer, url, page = sign_in(customer_email, "N. Perera", "customer", 1)
    assert url.endswith("/dashboard") and "Accounts" in page
    _, body = visit(customer, "/api/v1/accounts/transactions?account_id=1")
    assert json.loads(body)["account"]["account_number"] == "241000128453"

    staff, url, page = sign_in(staff_email, "Service Officer", "staff")
    assert url.endswith("/staff") and "Account lookup" in page
    print("Customer and staff workflows passed.")
finally:
    for email in created:
        docker(
            "php", "-r",
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$s=$p->prepare("DELETE FROM users WHERE email=?"); $s->execute([$argv[1]]);',
            "--", email,
        )
