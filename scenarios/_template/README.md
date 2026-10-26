# Scenario template

Copy this entire folder to a new name before editing it. The template only serves a harmless placeholder page.

Fill in before calling the exercise complete:

- **Scenario title and identifier:**
- **Advisory/CVE or application flaw:**
- **Affected PHP or app version:**
- **Learning goal and expected result:**
- **Local setup and reset:**
- **Verification and secure fix:**

Run from this copied scenario's folder with `docker compose up --build -d`; open <http://localhost:8081>. Change the host port if another scenario uses it. Stop with `docker compose down`.

This Compose stack has its own network and no shared volume, database, or credentials from the core app.
