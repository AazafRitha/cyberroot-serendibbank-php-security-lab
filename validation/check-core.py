"""Smoke-check the running localhost core portal. Requires Docker Compose and Python 3."""

import http.cookiejar
import json
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


def current_otp(email):
    result = subprocess.run(
        ["docker", "compose", "exec", "-T", "web", "php", "scripts/read-otp.php", email],
        check=True, capture_output=True, text=True,
    )
    code = result.stdout.strip()
    assert re.fullmatch(r"\d{6}", code), "Verification outbox did not return a code"
    return code


def sign_in(opener, email, password, bypass=False):
    status, _, html = visit(opener, "/login")
    assert status == 200
    status, url, html = visit(opener, "/login", {"_csrf": token(html, "_csrf"), "email": email, "password": password})
    assert status == 200 and url.endswith("/verify"), (status, url)
    if bypass:
        assert visit(opener, "/dashboard")[1].endswith("/login"), "Dashboard accessible before OTP"
        status, _, html = visit(opener, "/verify")
        assert status == 200
        for _ in range(6):
            status, _, invalid = visit(opener, "/verify", {"_csrf": token(html, "_csrf"), "code": "111111"})
            assert status == 422 and "could not be accepted" in invalid
        code = "000000"
    else:
        code = current_otp(email)
    status, url, html = visit(opener, "/verify", {"_csrf": token(html, "_csrf"), "code": code})
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

unknown = browser()
_, _, login_page = visit(unknown, "/login")
status, _, missing_user = visit(unknown, "/login", {"_csrf": token(login_page, "_csrf"), "email": "missing@example.test", "password": "wrong"})
assert status == 401 and "No account was found" in missing_user
for _ in range(6):
    status, _, wrong_password = visit(unknown, "/login", {"_csrf": token(login_page, "_csrf"), "email": "ci.customer1@example.test", "password": "wrong"})
    assert status == 401 and "The password is incorrect" in wrong_password

customer1 = browser()
url, dashboard = sign_in(customer1, "ci.customer1@example.test", p1, bypass=True)
assert url.endswith("/dashboard") and "Welcome, N. Perera" in dashboard
assert "•••• 8453" in dashboard and "•••• 8455" not in dashboard
status, _, other_statement = visit(customer1, "/statement?account=3")
assert status == 200 and "M. Fernando" in other_statement and "241000128455" in other_statement

status, _, all_branches = visit(customer1, "/locations/search?q=" + urllib.parse.quote("' OR 1=1 #"))
assert status == 200 and all(city in all_branches for city in ("Colombo", "Kandy", "Galle", "Jaffna"))

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
status, _, dashboard = visit(customer1, "/transfer/repeat?from=1&to=2&amount=5.00")
assert status == 200 and "184235.75" in dashboard, "State-changing repeat-transfer link did not run"

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

status, _, chat_data = visit(customer1, "/chat")
assert status == 200
chat_token = json.loads(chat_data)["csrf"]
status, _, answer = visit(customer1, "/chat", {"_csrf": chat_token, "message": "<script>alert(1)</script>"})
assert status == 200 and "reply" in json.loads(answer)
status, _, transcript = visit(customer1, "/chat/transcript")
assert status == 200 and "&lt;script&gt;alert(1)&lt;/script&gt;" in transcript
status, _, dashboard = visit(customer1, "/chat/escalate", {"_csrf": token(transcript, "_csrf")})
assert status == 200 and "Your conversation was sent" in dashboard

customer2 = browser()
url, dashboard2 = sign_in(customer2, "ci.customer2@example.test", p2)
assert url.endswith("/dashboard") and "•••• 8455" in dashboard2 and "•••• 8453" not in dashboard2
assert "Test message" not in dashboard2

staff = browser()
url, workspace = sign_in(staff, "ci.staff@example.test", staff_password)
assert url.endswith("/staff") and "Service workspace" in workspace
assert "&lt;script&gt;alert(1)&lt;/script&gt;" in workspace
assert "<script>alert(1)</script>" in workspace, "Assistant transcript was safely escaped in lab mode"
assert visit(staff, "/transfer")[0] == 403
assert visit(customer1, "/staff")[0] == 403

print("Core portal smoke checks passed")
