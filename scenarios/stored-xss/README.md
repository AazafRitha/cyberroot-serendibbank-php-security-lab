# Stored XSS: secure messages

Start with `docker compose up --build -d` in this folder and open <http://localhost:8083>. Messages are retained in the local PHP session and deliberately rendered without HTML escaping. This is an isolated exercise, not the core portal. See [validation](../../validation/stored-xss.md). Stop with `docker compose down`.
