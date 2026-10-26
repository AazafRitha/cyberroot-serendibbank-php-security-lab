# SQL injection: branch finder

1. In `scenarios/sql-injection`, run `docker compose up --build -d` and wait for the database health check.
2. Visit <http://localhost:8082/?q=Colombo>. A normal city search returns the Colombo branch.
3. Enter `' OR 1=1 -- ` in the City field. The final space after `--` matters in MySQL.
4. **Expected flaw:** the search returns every branch because the input changes the SQL predicate. Search text is escaped in HTML, but the query is still unsafe.
5. Compare with the core application's prepared PDO queries. User text must be bound as data, including when used in a LIKE search.

Reset: `docker compose down` in this scenario folder. Its database is ephemeral. Fix concept: `prepare('... WHERE city LIKE ?')` and bind `%` + input + `%`.
