# CSRF: pay a saved payee

Start with `docker compose up --build -d` in this folder and open <http://localhost:8084>. A transfer changes session state without checking a request token. This is an isolated exercise, not the core portal. See [validation](../../validation/csrf-transfer.md). Stop with `docker compose down`.
