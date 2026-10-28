"""Send a sign-in code over SMTP to local Mailpit and verify its recipient."""

import json
import subprocess
import time
import urllib.request
import uuid


address = f"smtp-check-{uuid.uuid4().hex[:12]}@example.test"
code = "481957"
subprocess.run(["docker", "compose", "--profile", "mailtest", "up", "-d", "mailpit"], check=True)
try:
    for _ in range(30):
        try:
            with urllib.request.urlopen("http://127.0.0.1:8025/api/v1/messages", timeout=3) as response:
                assert response.status == 200
            break
        except (OSError, AssertionError):
            time.sleep(1)
    else:
        raise AssertionError("Mailpit did not start")

    subprocess.run([
        "docker", "compose", "run", "--rm", "--no-deps",
        "-e", "MAIL_TRANSPORT=smtp", "-e", "SMTP_HOST=mailpit", "-e", "SMTP_PORT=1025",
        "-e", "SMTP_ENCRYPTION=none", "-e", "MAIL_FROM_ADDRESS=security@serendibbank.example",
        "web", "php", "-r",
        'require "app/bootstrap.php"; \\CyberRoot\\SerendibBank\\Core\\MailDelivery::sendSignInCode($argv[1], $argv[2]);',
        "--", address, code,
    ], check=True, capture_output=True, text=True)

    with urllib.request.urlopen("http://127.0.0.1:8025/api/v1/messages", timeout=5) as response:
        messages = json.load(response)["messages"]
    match = next((item for item in messages if any(recipient["Address"] == address for recipient in item["To"])), None)
    assert match is not None, "Code was not addressed to the expected account email"
    with urllib.request.urlopen(f"http://127.0.0.1:8025/view/{match['ID']}.txt", timeout=5) as response:
        body = response.read().decode()
    assert code in body and "5 minutes" in body
    print("SMTP delivery reached the specified recipient in Mailpit.")
finally:
    subprocess.run(["docker", "compose", "--profile", "mailtest", "stop", "mailpit"], check=True)
