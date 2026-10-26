# CSRF: pay a saved payee

1. In `scenarios/csrf-transfer`, run `docker compose up --build -d`.
2. Visit <http://localhost:8084>. The starting fictional balance is LKR 1,000.
3. In your browser developer console while on that page, run `fetch('/', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'payee=Test&amount=100'}).then(() => location.reload())`.
4. **Expected flaw:** the balance becomes LKR 900 and the transfer appears even though the request has no anti-CSRF token. The same-origin console check validates the missing server-side token check; a real cross-site exploit also depends on browser cookie policy.
5. Compare with the core `/transfer` form. A POST without its session CSRF token must be rejected and leave balances unchanged.

Reset: clear the browser cookies for `localhost:8084`; then `docker compose down`. Fix concept: token validation on every state-changing request plus suitable cookie settings.
