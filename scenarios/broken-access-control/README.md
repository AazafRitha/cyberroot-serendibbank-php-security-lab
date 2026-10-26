# Broken access control: account statement

The page presents customer 1 as the signed-in fixture but uses the `account` query parameter without checking ownership. Account 2 belongs to a different fictional customer. This is an isolated, local-only instance on `127.0.0.1:8081`; it does not share the core database or session.

Run `docker compose up --build -d` in this folder. Compare `/?account=1` with `/?account=2`. The second URL exposes M. Fernando's statement and confirms the weakness. See [the validation guide](../../validation/broken-access-control.md) for commands and expected results.

The secure counterpart is the core dashboard's customer-scoped account query. Reset with `docker compose down`.
