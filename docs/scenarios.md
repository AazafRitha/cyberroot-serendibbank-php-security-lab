# Isolated security scenarios

The core app is a reference demo. Each intentionally vulnerable scenario must live in its own folder under `scenarios/` and run as a separate Compose project. The included `_template` is harmless and can be copied when the first PHP advisory or application flaw is selected.

## Scenario checklist

For each exercise, document:

- The exact vulnerability and source advisory or CVE, if applicable
- Affected and patched PHP versions or application conditions
- Learning goal, expected behavior, and verification steps
- Local port, container image, and any database or fixture data
- How to reset the exercise
- A secure version or remediation notes

Do not mount the core database volume into a scenario. Use a distinct port and local-only bind. Never publish a scenario to an internet-facing host. Do not add real personal, banking, or credential data.

## Use the template

Copy `scenarios/_template` to a new descriptive folder, then update the copied README and implementation. The template runs a standalone PHP page on `127.0.0.1:8081`:

```powershell
Set-Location scenarios/your-scenario-name
docker compose up --build -d
```

Start scenarios only when you need them, and stop each with `docker compose down`. The template has no route into the core application's network or MySQL volume.
