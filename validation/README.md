# Exercise validation

Each exercise runs in its own Docker Compose project and binds to `127.0.0.1` only. Start one from its scenario folder with `docker compose up --build -d`, follow the linked check, then run `docker compose down`. These are intentionally vulnerable teaching examples and must not be added to the public VM's core application. Use only fictional data and systems you control.

| Exercise | Banking area | Local URL | Check |
| --- | --- | --- | --- |
| Broken access control (IDOR) | Account statement | `http://localhost:8081` | [steps](broken-access-control.md) |
| SQL injection | Branch finder | `http://localhost:8082` | [steps](sql-injection.md) |
| Stored XSS | Secure messages | `http://localhost:8083` | [steps](stored-xss.md) |
| CSRF | Pay a saved payee | `http://localhost:8084` | [steps](csrf-transfer.md) |
| Weak login | Online banking | `http://localhost:8085` | [steps](weak-login.md) |

The core portal has [functional checks](core-portal.md) for authentication, ownership, form validation, role separation, and responsive layout. The intended flaws are confined to `scenarios/`. Verify them locally before any separately reviewed challenge deployment. The original idea of a specific PHP advisory was never identified by CVE, so no such advisory is claimed here.
