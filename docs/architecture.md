# Architecture

The core demo follows a small Model–View–Controller layout:

```text
Browser → Apache public/ → public/index.php → Router → Controller
                                                ↓          ↓
                                               View ← Model → MySQL
```

`public/` is Apache's document root, so application classes, database setup files, and `.env` are outside the web-accessible directory. The front controller registers the two GET routes. The dashboard controller reads only the fictional customer with ID 1; there is **no authentication or authorization system** in this starter.

`Account` uses native PDO prepared statements. The view renderer supplies a common HTML escaping helper. A database failure produces a generic browser message and writes details to the container's error log.

The database service has a named Docker volume, no published port, and only an internal database network. The web service joins that network and a separate frontend network, with its HTTP port published only to host loopback. MySQL runs `database/init.sql` when its data volume is first created.

## Next core features

1. Add authentication, sessions, and authorization before creating account-specific routes.
2. Add migrations for schema changes instead of editing the initial seed file after deployment.
3. Add tests for account ownership, transaction integrity, and financial calculations before any write operations.
4. Keep deliberately vulnerable variants outside `app/` and `public/`; follow [scenarios.md](scenarios.md).
