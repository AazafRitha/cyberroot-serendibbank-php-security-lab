# Broken access control: account statement

1. In `scenarios/broken-access-control`, run `docker compose up --build -d`.
2. Visit <http://localhost:8081/?account=1>. The page says the signed-in fixture is N. Perera and shows account `241000128453`.
3. Change only the URL to <http://localhost:8081/?account=2>.
4. **Expected flaw:** the response reveals M. Fernando's account `241000128455` although the fixture identity has not changed.
5. Compare with the core `/dashboard`: sign in as each customer; another customer's account must never be listed or usable as a transfer source or destination.

Reset: `docker compose down` in this scenario folder. Fix concept: resolve account IDs through a customer-scoped query and return 404 for a nonowned resource.
