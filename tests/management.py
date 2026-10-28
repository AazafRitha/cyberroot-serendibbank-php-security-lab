"""Check the separate management host, user controls, and existing role-tamper path."""

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
created = []


def docker(*args):
    return subprocess.run(
        ["docker", "compose", "exec", "-T", "web", *args],
        check=True, capture_output=True, text=True,
    ).stdout.strip()


def visit(opener, path, fields=None, management=False):
    body = None if fields is None else urllib.parse.urlencode(fields).encode()
    headers = {"Host": HOST} if management else {}
    request = urllib.request.Request(BASE + path, data=body, headers=headers)
    try:
        with opener.open(request, timeout=20) as response:
            return response.status, response.url, response.read().decode()
    except urllib.error.HTTPError as error:
        return error.code, error.url, error.read().decode()


def csrf(page):
    match = re.search(r'name="_csrf" value="([^"]+)"', page)
    assert match is not None
    return match.group(1)


def new_user(role, customer_id=None):
    email = f"management-{role}-{uuid.uuid4().hex[:12]}@example.test"
    args = ["php", "scripts/create-user.php", email, f"Test {role.title()}", role]
    if customer_id is not None:
        args.append(str(customer_id))
    output = docker(*args)
    created.append(email)
    password = re.search(r"^Password: (.+)$", output, re.MULTILINE).group(1)
    user_id = int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", email,
    ))
    return email, password, user_id


def sign_in(email, password, management):
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
    status, _, login = visit(opener, "/login", management=management)
    assert status == 200
    status, url, _ = visit(opener, "/login", {
        "_csrf": csrf(login), "email": email, "password": password,
    }, management=management)
    assert status == 200 and url.endswith("/verify")
    _, _, verify = visit(opener, "/verify", management=management)
    code = docker("php", "scripts/read-otp.php", email)
    status, url, dashboard = visit(opener, "/verify", {
        "_csrf": csrf(verify), "code": code,
    }, management=management)
    assert status == 200
    return opener, url, dashboard


try:
    admin_email, admin_password, _ = new_user("admin")
    staff_email, staff_password, _ = new_user("staff")
    customer_email, customer_password, customer_id = new_user("customer", 1)
    admin, url, dashboard = sign_in(admin_email, admin_password, True)
    assert url.endswith("/") and "People &amp; permissions" in dashboard
    assert customer_email in dashboard and "Cases in motion" in dashboard

    staff, url, staff_dashboard = sign_in(staff_email, staff_password, True)
    assert url.endswith("/") and "Cases in motion" in staff_dashboard
    assert "People &amp; permissions" not in staff_dashboard

    status, _, dashboard = visit(admin, "/users/update", {
        "_csrf": csrf(dashboard), "id": str(customer_id), "action": "suspend",
    }, management=True)
    assert status == 200 and "Suspended" in dashboard
    status, _, dashboard = visit(admin, "/users/update", {
        "_csrf": csrf(dashboard), "id": str(customer_id), "action": "activate",
    }, management=True)
    assert status == 200 and "User access updated" in dashboard

    customer, url, _ = sign_in(customer_email, customer_password, False)
    assert url.endswith("/dashboard")
    status, _, settings = visit(customer, "/settings")
    assert status == 200
    status, _, _ = visit(customer, "/settings/profile", {
        "_csrf": csrf(settings), "display_name": "Test Customer",
        "email": customer_email, "role": "admin",
    })
    assert status == 403
    promoted_role = docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT role FROM users WHERE email=?"); $s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", customer_email,
    )
    assert promoted_role == "admin"
    promoted, url, dashboard = sign_in(customer_email, customer_password, True)
    assert url.endswith("/") and "People &amp; permissions" in dashboard
    print("Management host, user controls, and role escalation passed.")
finally:
    for email in created:
        docker(
            "php", "-r",
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$s=$p->prepare("DELETE FROM users WHERE email=?"); $s->execute([$argv[1]]);',
            "--", email,
        )
