# Core portal checks

After `docker compose up --build -d` in the project root, provision two fictional customers and one staff account using `docker compose exec web php scripts/create-user.php EMAIL 'Name' customer CUSTOMER_ID` (or `staff` with no customer ID). Save generated passwords outside the repository. The customer IDs from the seeded database are `1` and `2`.

1. Open <http://localhost:8080/login>. Wrong credentials give a generic message; correct customer credentials reach `/dashboard`. Staff credentials reach `/staff`. Sign out and verify protected pages return to login.
2. As customer 1, inspect accounts and recent activity. The `/dashboard` view contains only customer 1 data; customer 2's account ID must not be accepted by the ordinary `/transfer` form or a debit-card application. The separate `/statement` route intentionally lacks ownership checking while lab mode is on; see [main-site checks](main-site.md).
3. Submit an own-account transfer with valid CSRF token; verify both balances and both activity rows change once. Repeat the same request token; no second movement should occur. Submit insufficient funds or a cross-customer account ID; balances must stay unchanged.
4. Submit an account application, a debit-card application linked to an owned account, and a support request. Their statuses should appear on the customer dashboard and staff dashboard. Staff can mark applications reviewed and requests resolved; a customer cannot call those actions.
5. Send a POST without CSRF token to each ordinary state-changing form; it must be rejected. The lab-mode `/transfer/repeat` GET is the intentional exception. Put `<script>alert(1)</script>` in a normal support message; staff must see literal text. The escalated assistant transcript is the intentional rendering exception in lab mode.
6. Check home, product pages, login, application, transfer, support, customer dashboard, and staff dashboard at narrow phone width and desktop width. Menus, tables, labels, error messages, and buttons must remain usable.

To reinitialize a **local fictional** database after schema changes, stop the root stack and remove only its volume with `docker compose down -v`, then start again. This permanently deletes that local Compose volume. Never use this reset on a public deployment.
