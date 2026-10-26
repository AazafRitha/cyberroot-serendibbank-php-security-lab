"""Smoke-check the running localhost core portal. Requires Docker Compose and Python 3."""

import http.cookiejar
import re
import subprocess
import time
import urllib.error
import urllib.parse
import urllib.request

BASE = "http://127.0.0.1:8080"


def visit(opener, path, fields=None):
    data = None if fields is None else urllib.parse.urlencode(fields).encode()
    request = urllib.request.Request(BASE + path, data=data)
    try:
        with opener.open(request, timeout=15) as response:
            return response.status, response.url, response.read().decode()
    except urllib.error.HTTPError as error:
        return error.code, error.url, error.read().decode()


def token(html, name):
    match = re.search(r'name="' + re.escape(name) + r'" value="([^"]+)"', html)
    assert match, f"Missing {name} input"
    return match.group(1)


def create_user(email, name, role, customer_id=None):
    command = ["docker", "compose", "exec", "-T", "web", "php", "scripts/create-user.php", email, name, role]
    if customer_id is not None:
        command.append(str(customer_id))
    result = subprocess.run(command, check=True, capture_output=True, text=True)
    match = re.search(r"^Password: (.+)$", result.stdout, re.MULTILINE)
    assert match, "Provisioning did not return a password"
    return match.group(1)


def browser():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def sign_in(opener, email, password):
    status, _, html = visit(opener, "/login")
    assert status == 200
    status, url, html = visit(opener, "/login", {"_csrf": token(html, "_csrf"), "email": email, "password": password})
    assert status == 200, (status, url)
    return url, html


for _ in range(30):
    try:
        if visit(browser(), "/")[0] == 200:
            break
    except (urllib.error.URLError, TimeoutError):
        time.sleep(2)
else:
    raise RuntimeError("Core website did not become ready")

p1 = create_user("ci.customer1@example.test", "N. Perera", "customer", 1)
p2 = create_user("ci.customer2@example.test", "M. Fernando", "customer", 2)
staff_password = create_user("ci.staff@example.test", "Service Officer", "staff")

customer1 = browser()
url, dashboard = sign_in(customer1, "ci.customer1@example.test", p1)
assert url.endswith("/dashboard") and "Welcome, N. Perera" in dashboard
assert "•••• 8453" in dashboard and "•••• 8455" not in dashboard

status, _, transfer = visit(customer1, "/transfer")
assert status == 200
status, _, _ = visit(customer1, "/transfer", {"from_account": 1, "to_account": 2, "amount": "10.00"})
assert status == 400, "Transfer accepted without CSRF token"
csrf = token(transfer, "_csrf")
request_token = token(transfer, "request_token")
status, _, _ = visit(customer1, "/transfer", {"_csrf": csrf, "request_token": request_token, "from_account": 1, "to_account": 3, "amount": "10.00"})
assert status == 422, "Cross-customer transfer accepted"
status, _, dashboard = visit(customer1, "/transfer", {"_csrf": csrf, "request_token": request_token, "from_account": 1, "to_account": 2, "amount": "10.00"})
assert status == 200 and "Transfer completed" in dashboard
assert "184240.75" in dashboard
status, _, _ = visit(customer1, "/transfer", {"_csrf": csrf, "request_token": request_token, "from_account": 1, "to_account": 2, "amount": "10.00"})
assert status == 422, "Repeated transfer form was accepted"

status, _, application = visit(customer1, "/apply/card")
assert status == 200
status, _, _ = visit(customer1, "/apply/card", {"_csrf": token(application, "_csrf"), "product": "Serendib Debit", "linked_account": 3, "notes": ""})
assert status == 422, "Foreign account accepted for debit card"
status, _, dashboard = visit(customer1, "/apply/card", {"_csrf": token(application, "_csrf"), "product": "Serendib Debit", "linked_account": 1, "notes": ""})
assert status == 200 and "Your application was received" in dashboard

status, _, support = visit(customer1, "/support/request")
assert status == 200
payload = "<script>alert(1)</script> This is a test message."
status, _, dashboard = visit(customer1, "/support/request", {"_csrf": token(support, "_csrf"), "category": "digital", "subject": "Test message", "message": payload})
assert status == 200 and "Your message was received" in dashboard

customer2 = browser()
url, dashboard2 = sign_in(customer2, "ci.customer2@example.test", p2)
assert url.endswith("/dashboard") and "•••• 8455" in dashboard2 and "•••• 8453" not in dashboard2
assert "Test message" not in dashboard2

staff = browser()
url, workspace = sign_in(staff, "ci.staff@example.test", staff_password)
assert url.endswith("/staff") and "Service workspace" in workspace
assert "&lt;script&gt;alert(1)&lt;/script&gt;" in workspace and "<script>alert(1)</script>" not in workspace
assert visit(staff, "/transfer")[0] == 403
assert visit(customer1, "/staff")[0] == 403

print("Core portal smoke checks passed")
