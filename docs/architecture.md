# Architecture

```text
Browser → Apache public/ → front controller → router → controller
                                               ↘ view  ↔ model → MySQL
```

`public/` is the only web root. Application classes, setup scripts, documentation, and `.env` remain outside it. The core PHP 8.3 app has public product pages plus a customer portal and staff service workspace. Accounts are scoped to the signed-in customer's ID. Staff can review applications and resolve support requests, but cannot access customer money movement.

Login uses PHP password hashes, a strict cookie session with regeneration after authentication, idle and absolute expiry, per-user session invalidation on password reset, generic error messages, and a database-backed attempt limit. POST forms check a session CSRF token. Views HTML-escape dynamic text. PDO statements bind input; transfers use a database transaction, row locks, ownership checks, balance conditions, and a request token to avoid duplicate movement. The core's CSP restricts scripts and frames.

The Docker stack binds HTTP only to host loopback and leaves MySQL on an internal network. `database/init.sql` initializes a fresh named volume with fictional data. Changing that file does **not** migrate an existing volume. Use a migration for retained data; `docker compose down -v` is only for a disposable local database.

These controls support a learning environment but do not establish a production bank. The [public VM plan](public-vm-plan.md) lists deployment work. Intentionally vulnerable variants run as separate localhost stacks under `scenarios/`, with [validation steps](../validation/README.md).
