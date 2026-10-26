"""Exercise the new public flows and their documented trust boundaries on Compose."""

import http.cookiejar
import re
import subprocess
import urllib.parse
import urllib.request
import uuid

BASE = "http://127.0.0.1:8080"


def visit(opener, path, fields=None):
    body = None if fields is None else urllib.parse.urlencode(fields).encode()
    with opener.open(urllib.request.Request(BASE + path, data=body), timeout=20) as response:
        return response.url, response.read().decode()


def csrf(page):
    return re.search(r'name="_csrf" value="([^"]+)"', page).group(1)


def docker_php(code, *args):
    return subprocess.run(
        ["docker", "compose", "exec", "-T", "web", "php", "-r", code, "--", *args],
        capture_output=True, check=True, text=True,
    ).stdout.strip()


def new_client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


created = []
staff_email = f"feature-staff-{uuid.uuid4().hex[:12]}@example.test"
try:
    browser = new_client()
    _, calculator = visit(browser, "/savings/calculator")
    assert "Savings calculator" in calculator and 'name="annual_rate" value="7.5"' in calculator
    _, estimate = visit(browser, "/savings/calculator", {
        "_csrf": csrf(calculator), "principal": "100000", "months": "12", "annual_rate": "7.5",
    })
    assert "LKR 7,500.00" in estimate and "LKR 107,500.00" in estimate
    _, altered = visit(browser, "/savings/calculator", {
        "_csrf": csrf(calculator), "principal": "100000", "months": "12", "annual_rate": "45",
    })
    assert "LKR 45,000.00" in altered and "LKR 145,000.00" in altered

    second = new_client()
    _, form = visit(browser, "/support/contact")
    _, second_form = visit(second, "/support/contact")
    marker = "<svg onload=alert(1)>feature-probe</svg>"
    url, tracked = visit(browser, "/support/contact", {
        "_csrf": csrf(form), "name": "A. Test", "email": "first@example.test",
        "category": "accounts", "subject": "Savings enquiry", "message": marker,
    })
    reference = urllib.parse.parse_qs(urllib.parse.urlparse(url).query)["ref"][0]
    created.append(reference)
    assert reference.startswith("SBH") and "Savings enquiry" in tracked
    assert "&lt;svg onload=alert(1)&gt;" in tracked
    _, other_view = visit(second, f"/support/track?ref={reference}")
    assert "first@example.test" in other_view and "feature-probe" in other_view

    url2, _ = visit(second, "/support/contact", {
        "_csrf": csrf(second_form), "name": "B. Test", "email": "second@example.test",
        "category": "cards", "subject": "Card enquiry", "message": "Please advise about cards.",
    })
    created.append(urllib.parse.parse_qs(urllib.parse.urlparse(url2).query)["ref"][0])

    password_output = subprocess.run(
        ["docker", "compose", "exec", "-T", "web", "php", "scripts/create-user.php",
         staff_email, "Service Officer", "staff"], capture_output=True, check=True, text=True,
    ).stdout
    password = re.search(r"^Password: (.+)$", password_output, re.MULTILINE).group(1)
    staff = new_client()
    _, login = visit(staff, "/login")
    visit(staff, "/login", {"_csrf": csrf(login), "email": staff_email, "password": password})
    _, verify = visit(staff, "/verify")
    otp = subprocess.run(
        ["docker", "compose", "exec", "-T", "web", "php", "scripts/read-otp.php", staff_email],
        capture_output=True, check=True, text=True,
    ).stdout.strip()
    _, inbox = visit(staff, "/verify", {"_csrf": csrf(verify), "code": otp})
    assert "Contact inbox" in inbox and marker in inbox
    enquiry_id = str(int(reference[3:]))
    assert f'name="id" value="{enquiry_id}"' in inbox
    _, updated = visit(staff, "/staff/contact/resolve", {"_csrf": csrf(inbox), "id": enquiry_id})
    assert "Enquiry resolved." in updated
    _, resolved = visit(browser, f"/support/track?ref={reference}")
    assert "Resolved" in resolved

    _, locations = visit(browser, "/locations")
    assert "openstreetmap.org/export/embed.html" in locations and 'data-map-location="Kandy City"' in locations
    print("Calculator, map, enquiry tracking and staff inbox passed.")
finally:
    for reference in created:
        docker_php(
            'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
            '$s=$p->prepare("DELETE FROM contact_requests WHERE id=?"); $s->execute([(int)substr($argv[1],3)]);',
            reference,
        )
    docker_php(
        'require "app/bootstrap.php"; $p=\\CyberRoot\\SerendibBank\\Core\\Database::connect(); '
        '$s=$p->prepare("DELETE FROM users WHERE email=?"); $s->execute([$argv[1]]);',
        staff_email,
    )
