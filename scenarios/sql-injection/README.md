# SQL injection: branch finder

The city search concatenates a query parameter into a SQL statement. The database contains only three fictional branch records and is reachable only inside this scenario network. The web service binds to `127.0.0.1:8082`.

Run `docker compose up --build -d` in this folder, then follow [the validation guide](../../validation/sql-injection.md). The core site uses prepared PDO statements. Reset with `docker compose down`.
