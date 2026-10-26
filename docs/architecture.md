# Architecture

```text
Browser → Apache public/ → front controller → router → controller
                                               ↘ view  ↔ model → MySQL
```

`public/` is the only web root. Application classes, setup scripts, documentation, and `.env` remain outside it. The core PHP 8.3 app has public product pages plus a customer portal and staff service workspace. Accounts are scoped to the signed-in customer's ID. Staff can review applications and resolve support requests, but cannot access customer money movement.

The local Compose stack sets `APP_LAB_MODE=1`. In that mode, the main site intentionally changes five behaviors: login reveals whether an email exists and skips attempt throttling; statements omit customer ownership checks; branch search concatenates input into SQL; repeat transfer changes balances on GET; and escalated assistant text renders raw in the staff workspace while CSP allows inline scripts. [Main-site validation](../validation/main-site.md) documents each input and expected result. With `APP_LAB_MODE=0`, those deliberate flaws are disabled while the normal login, statement, search, transfer, and support flows continue.

Outside those deliberate paths, PHP password hashes, strict cookie sessions, session regeneration, idle and absolute expiry, CSRF checks on ordinary POST forms, HTML escaping, bound PDO queries, and transactional own-account transfers remain. This is a fictional training system, not a production banking security reference.

The Docker stack binds HTTP only to host loopback and leaves MySQL on an internal network. `database/init.sql` initializes a fresh named volume with fictional data. Changing that file does **not** migrate an existing volume. Use a migration for retained data; `docker compose down -v` is only for a disposable local database.

The [public VM plan](public-vm-plan.md) lists deployment work. Standalone variants remain under `scenarios/` for focused practice. They use separate localhost stacks and are covered by the [validation matrix](../validation/README.md).
