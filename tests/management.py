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
created_account_id = None


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
    super_email, super_password, _ = new_user("super_admin")
    staff_email, staff_password, _ = new_user("staff")
    customer_email, customer_password, customer_id = new_user("customer", 1)
    admin, url, dashboard = sign_in(admin_email, admin_password, True)
    assert url.endswith("/") and "People &amp; permissions" in dashboard
    assert customer_email in dashboard and "Cases in motion" in dashboard

    staff, url, staff_dashboard = sign_in(staff_email, staff_password, True)
    assert url.endswith("/") and "Cases in motion" in staff_dashboard
    assert "People &amp; permissions" not in staff_dashboard
    assert "Bank accounts" in staff_dashboard and "Transaction review" in staff_dashboard

    super_user, url, super_dashboard = sign_in(super_email, super_password, True)
    assert url.endswith("/") and "Activity log" in super_dashboard and "Add team member" in super_dashboard
    employee_email = f"new-staff-{uuid.uuid4().hex[:12]}@example.test"
    status, _, super_dashboard = visit(super_user, "/users/create", {
        "_csrf": csrf(super_dashboard), "name": "New Service Officer",
        "email": employee_email, "role": "staff",
    }, management=True)
    assert status == 200 and employee_email in super_dashboard and "Temporary password" in super_dashboard
    created.append(employee_email)
    employee_id = int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM users WHERE email=?"); $s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", employee_email,
    ))
    status, _, super_dashboard = visit(super_user, "/users/update", {
        "_csrf": csrf(super_dashboard), "id": str(employee_id), "action": "admin",
    }, management=True)
    assert status == 200 and "User access updated" in super_dashboard

    status, _, _ = visit(staff, "/accounts/status", {
        "_csrf": csrf(staff_dashboard), "id": "1", "status": "frozen",
    }, management=True)
    assert status == 403

    status, _, _ = visit(admin, "/users/update", {
        "_csrf": csrf(dashboard), "id": str(new_user("staff")[2]), "action": "admin",
    }, management=True)
    assert status == 403

    status, _, super_dashboard = visit(super_user, "/accounts/open", {
        "_csrf": csrf(super_dashboard), "customer_id": "1", "account_type": "Savings account",
    }, management=True)
    assert status == 200 and "opened for the customer" in super_dashboard
    number = re.search(r"Account (241\d{9}) opened", super_dashboard).group(1)
    created_account_id = int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM accounts WHERE account_number=?"); $s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", number,
    ))
    status, _, staff_dashboard = visit(staff, "/ledger/request", {
        "_csrf": csrf(staff_dashboard), "account_id": str(created_account_id),
        "direction": "credit", "amount": "12.50", "reason": "Operations test credit",
    }, management=True)
    assert status == 200 and "second team member" in staff_dashboard
    request_id = int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM ledger_requests WHERE account_id=? ORDER BY id DESC LIMIT 1"); '
        '$s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", str(created_account_id),
    ))
    status, _, _ = visit(staff, "/ledger/decide", {
        "_csrf": csrf(staff_dashboard), "id": str(request_id), "decision": "approved",
    }, management=True)
    assert status == 403
    status, _, dashboard = visit(admin, "/ledger/decide", {
        "_csrf": csrf(dashboard), "id": str(request_id), "decision": "approved",
    }, management=True)
    assert status == 200 and "Ledger request approved" in dashboard
    balance = docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT balance FROM accounts WHERE id=?"); $s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", str(created_account_id),
    )
    assert balance == "12.50"
    status, _, dashboard = visit(admin, "/accounts/status", {
        "_csrf": csrf(dashboard), "id": str(created_account_id), "status": "frozen",
    }, management=True)
    assert status == 200 and "Account status updated" in dashboard
    blocked = docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        'try {(new \\CyberRoot\\SerendibBank\\Models\\Account($p))->transfer('
        '1, 1, (int)$argv[1], "1.00", bin2hex(random_bytes(32))); echo "allowed";} '
        'catch (DomainException $e) {echo "blocked";}',
        "--", str(created_account_id),
    )
    assert blocked == "blocked"
    status, _, dashboard = visit(admin, "/accounts/status", {
        "_csrf": csrf(dashboard), "id": str(created_account_id), "status": "active",
    }, management=True)
    assert status == 200 and "Account status updated" in dashboard
    transaction_id = int(docker(
        "php", "-r",
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("SELECT id FROM transactions WHERE account_id=? ORDER BY id DESC LIMIT 1"); '
        '$s->execute([$argv[1]]); echo $s->fetchColumn();',
        "--", str(created_account_id),
    ))
    status, _, staff_dashboard = visit(staff, "/transactions/review", {
        "_csrf": csrf(staff_dashboard), "id": str(transaction_id),
        "status": "flagged", "note": "Review this posting",
    }, management=True)
    assert status == 200 and "Transaction review recorded" in staff_dashboard
    status, _, _ = visit(staff, "/transactions/review", {
        "_csrf": csrf(staff_dashboard), "id": str(transaction_id),
        "status": "cleared", "note": "Reviewed",
    }, management=True)
    assert status == 403
    status, _, dashboard = visit(admin, "/transactions/review", {
        "_csrf": csrf(dashboard), "id": str(transaction_id),
        "status": "cleared", "note": "Reviewed",
    }, management=True)
    assert status == 200 and "Transaction review recorded" in dashboard

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
    if created_account_id is not None:
        docker(
            "php", "-r",
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$id=$argv[1]; $p->prepare("DELETE FROM transaction_reviews WHERE transaction_id IN '
            '(SELECT id FROM transactions WHERE account_id=?)")->execute([$id]); '
            'foreach (["ledger_requests", "transactions"] as $table) '
            '{$s=$p->prepare("DELETE FROM $table WHERE account_id=?"); $s->execute([$id]);} '
            '$s=$p->prepare("DELETE FROM accounts WHERE id=?"); $s->execute([$id]);',
            "--", str(created_account_id),
        )
    for email in created:
        docker(
            "php", "-r",
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$s=$p->prepare("DELETE FROM users WHERE email=?"); $s->execute([$argv[1]]);',
            "--", email,
        )
