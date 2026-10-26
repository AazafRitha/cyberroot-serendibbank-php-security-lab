# Exercise validation

The [main site](main-site.md) now has five integrated intentional weaknesses when `APP_LAB_MODE=1`. Start at <http://localhost:8080> and use the real customer and staff flows to validate them. Use only fictional data and systems you control.

The original focused exercises below also run as independent Compose projects, each bound to `127.0.0.1`. Start one from its scenario folder with `docker compose up --build -d`, follow its linked check, then run `docker compose down`.

| Exercise | Banking area | Local URL | Check |
| --- | --- | --- | --- |
| Broken access control (IDOR) | Account statement | `http://localhost:8081` | [steps](broken-access-control.md) |
| SQL injection | Branch finder | `http://localhost:8082` | [steps](sql-injection.md) |
| Stored XSS | Secure messages | `http://localhost:8083` | [steps](stored-xss.md) |
| CSRF | Pay a saved payee | `http://localhost:8084` | [steps](csrf-transfer.md) |
| Weak login | Online banking | `http://localhost:8085` | [steps](weak-login.md) |

The portal has [functional checks](core-portal.md) for forms, role separation, and layout; in lab mode, the [main-site guide](main-site.md) records where normal security checks are deliberately missing. The original idea of a specific PHP advisory was never identified by CVE, so no such advisory is claimed here.
