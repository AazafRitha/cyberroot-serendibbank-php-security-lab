# Weak login: account enumeration and unlimited attempts

1. In `scenarios/weak-login`, run `docker compose up --build -d`.
2. At <http://localhost:8085>, submit `unknown@example.test` with any password. The page says no account exists.
3. Submit `n.perera@example.test` with a wrong password. The page says the password is incorrect. This distinction reveals which email exists.
4. Submit the wrong password several more times. **Expected flaw:** the response never changes to an attempt limit. The local fixture password `Lotus2026!` succeeds.
5. Compare with the core `/login`: use a generated test account, check that errors are generic and repeated failures trigger a temporary limit.

Reset: `docker compose down`. Fix concept: hashed passwords, generic errors, attempt throttling, and monitoring.
