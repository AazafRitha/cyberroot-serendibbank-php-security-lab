# Weak login: enumeration and no rate limit

Start with `docker compose up --build -d` in this folder and open <http://localhost:8085>. The local fixture account is `n.perera@example.test` with password `Lotus2026!`. Distinct errors reveal whether an email exists, and attempts are unlimited. This is an isolated exercise, not the core portal. See [validation](../../validation/weak-login.md). Stop with `docker compose down`.
