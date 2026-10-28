# SerendibBank security testing guide

This repository intentionally contains web and API security weaknesses for authorized testing. The public website presents normal banking journeys. Use this guide on the local Docker instance or an operator-approved, isolated deployment with invented accounts and records. It is a validation guide, not a claim that every scanner alert is a distinct finding.

## Prepare a local test

1. Start the site with `docker compose up --build -d`. Confirm that `docker compose ps` shows a healthy database and a running web service. The local URL is `http://127.0.0.1:8080`.
2. Create two customer logins, one linked to customer 1 and one to customer 2, plus one staff login using the commands in [README](../README.md#run-locally). Save their generated passwords privately. Read local verification codes with `docker compose exec web php scripts/read-otp.php <email>`.
3. Use separate browser profiles for the two customers and staff. Record the exact request, response, role, and record identifier for each result. Use only test data.
4. Run `python tests/smoke.py`, `python tests/new-features.py`, and `python tests/settings-workflows.py` for baseline checks. These create and remove their own temporary users and enquiries. Some findings below require browser or proxy verification.

## Recommended tools

Use a browser and its Developer Tools to find forms, links, JavaScript requests, cookies, and response headers. For modified requests, use [Burp Repeater](https://portswigger.net/burp/documentation/desktop/tools/repeater/http-messages) or another manual HTTP client such as `curl`; keep the session cookie and CSRF token from your own test account. Use two separate browser profiles for ownership checks. The local Docker CLI helps create fictional users, read local verification codes, and reset test data. See the [OWASP IDOR testing guide](https://wstg.owasp.org/latest/4-Web_Application_Security_Testing/05-Authorization/04-Insecure_Direct_Object_References/) for the two-account comparison method and the [OWASP API Top 10](https://api-security.owasp.org/editions/2023/en/0x11-t10/) for API authorization categories.

## Findings and ways to verify them

| # | Area and entry point | Validation method and expected evidence |
| --- | --- | --- |
| 1 | Sign-in enumeration, `/login` | Compare responses for an unknown email and a known email with a wrong password. The messages differ. |
| 2 | Failed-login cooldown, `/login` | Make five failed sign-in attempts for one test login and observe the 15-minute cooldown. Repeat with a different `X-Forwarded-For` value; the IP-based attempt key changes and requests can proceed. |
| 3 | Verification bypass, `/verify` | After the correct password, submit `000000` instead of the delivered code. The session completes sign-in. |
| 4 | Verification attempt limit, `/verify` | After the correct password, submit six incorrect codes and then the correct code. The flow remains usable. |
| 5 | Statement object access, `/statement?account=3` | Sign in as customer 1 and request customer 2's account ID. The other customer's statement appears. |
| 6 | Branch SQL injection, `/locations/search?q=` | Compare a normal city search with an SQL predicate such as `' OR '1'='1`. The result set expands beyond the city. |
| 7 | Reflected XSS, `/offers/search?q=` | Submit a harmless HTML marker through the search parameter and inspect the response source. The marker is emitted as markup rather than encoded text. Use an isolated browser if demonstrating execution. |
| 8 | Application workflow tampering, `POST /apply/account` or `/apply/card` | Submit a valid application while adding `status=reviewed` to the request. Inspect the staff list; it skips pending review. |
| 9 | Open redirect, `/digital/continue?next=` | Supply an external HTTPS URL and inspect the `Location` response header. It points to the supplied host. |
| 10 | GET-driven transfer, `/transfer/repeat` | As a customer, visit a valid repeat-transfer URL for two of your own accounts and compare their balances before and after. A GET request changes balances. Restore the local database after this check. |
| 11 | Assistant handoff stored XSS, `/chat/escalate` → `/staff` | Send a harmless HTML marker in a chat, escalate it, and inspect the staff support item. The transcript is rendered as HTML. |
| 12 | Assistant instruction override, `/chat` | Ask the PHP assistant to ignore its normal guidance and disclose its internal service brief. Its response can contain the staff queue label. This is a deterministic PHP bot, not an LLM model exploit. |
| 13 | Account activity API object access, `/api/v1/accounts/transactions?account_id=3` | As customer 1, fetch customer 2's account ID. The JSON includes the other account's metadata and activity. |
| 14 | Support API object access, `/api/v1/support/requests?request_id=` | Create a request as customer 2; as customer 1, fetch its numeric ID. The JSON exposes the other customer's case. |
| 15 | Application API function authorization, `POST /api/v1/applications/review` | As a customer, send a valid review request for a test application. Its status changes without a staff role. |
| 16 | Beneficiary lookup disclosure, `/api/v1/beneficiaries/lookup?account_number=` | Without signing in, submit a seeded account number. The JSON includes holder details, balance, and internal identifiers. |
| 17 | Quick transfer amount direction, `POST /api/v1/transfers/quick` | As a customer, use two of your own accounts and a negative amount. The balance movement reverses direction while the recorded transfer amount is positive. Reset balances afterward. |
| 18 | Active support attachment, `/support/request` → `/staff` | Submit an SVG attachment to an account-specific support request even though the form advertises image/PDF formats. Staff receive a same-origin attachment link. Use only harmless SVG content when validating. |
| 19 | Product document inclusion, `/documents/view?file=` | Compare `file=everyday-account` with `file=../internal/service-routing`. The second request includes an internal service reference. Traversal outside the app content root is blocked. |
| 20 | Calculator rate tampering, `POST /savings/calculator` | Calculate LKR 100,000 for 12 months using the form's 7.5% rate: interest is LKR 7,500. Change hidden `annual_rate` to `45` and resend the same request: interest becomes LKR 45,000. The server trusts a client-supplied rate. This affects an indicative quote, not an account balance. |
| 21 | Enquiry tracking object access, `/support/track?ref=SBH000001` | Submit two general enquiries from separate browser profiles. Take one returned reference and open it in the other profile without signing in. The name, email, and message are visible. References are sequential. |
| 22 | General enquiry stored XSS, `/support/contact` → `/staff` | Submit a harmless HTML marker in the public enquiry message. Its tracking page encodes the text, but the staff Contact inbox emits it as HTML. Use a disposable staff account and isolated browser for execution proof. |
| 23 | Profile role escalation, `POST /settings/profile` | As a customer, update normal profile fields while adding `role=staff` to the request. The next navigation reaches the staff workspace. The server accepts a privileged property from the customer request. Treat as a critical authorization finding on this fictional dataset. |
| 24 | Password target alteration, `POST /settings/security` | Create two disposable customer logins. As customer 1, submit a valid current password and new password but change the hidden `user_id` to customer 2's user ID. Customer 2 can then sign in with the new password. Treat as a critical account-takeover finding on this fictional dataset. |

For a concrete calculator check, keep the session cookie and `_csrf` token from the calculator page, then alter only the hidden `annual_rate` field in the outgoing POST. For the enquiry check, submit through the form and use the reference returned in the redirect. The `tests/new-features.py` script confirms both behaviors and removes its temporary records. For the two settings findings, compare the original request with a single changed field; `tests/settings-workflows.py` confirms both and removes its temporary users. Avoid testing against someone else's real login.

## Reporting and reset

A useful report includes the affected page or endpoint, test account role, exact request and response, expected versus actual behavior, impact on a fictional record, and a short reproduction path. Group reports that share one root cause. Avoid real customer information, unrelated systems, bulk scanning, or destructive transfers.

The local Compose web service is bound to `127.0.0.1`; the database is on an internal Docker network. An external testing VM requires its own scope, isolated network, fictional data, monitoring, TLS, and a reset plan. Do not expose the local Compose stack by changing its port binding alone. `docker compose down -v` removes the local database volume and recreates the seed data on the next start.
