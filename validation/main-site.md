# Main-site security exercise checks

The root Compose stack enables `APP_LAB_MODE=1` and binds the website to <http://localhost:8080>. These flaws are deliberately part of the realistic banking UI during local practice. Use only the seeded fictional customers. Start the root stack with `docker compose up --build -d`; an existing `.env` is preserved. Recreate the web service after changing `APP_LAB_MODE` with `docker compose up -d --force-recreate web`.

Create two customer logins and one staff login if needed (see the [README](../README.md#run-on-your-windows-laptop)). The seeded accounts are customer 1: IDs `1`, `2`; customer 2: IDs `3`, `4`. Save generated passwords privately. After a correct password, the site requests a six-digit OTP at `/verify`. To complete the ordinary flow, the operator reads it with `docker compose exec web php scripts/read-otp.php 'customer1@example.test'`. The outbox is available only inside Docker, and the code expires in five minutes. The `validation/check-core.py` script exercises the same behaviors in CI.

## Weak login

At `/login`, try an unknown email with any password, then a real fictional email with a wrong password. **Expected:** different messages reveal whether the account exists. Repeat the wrong password more than five times; the form keeps accepting attempts. When `APP_LAB_MODE=0`, errors become generic and failed attempts are throttled.

## OTP verification bypass

Sign in with a valid fictional email and password. The browser reaches `/verify`; trying `/dashboard` before verification sends you back to sign in. Ask the operator for the current code, or have them run the `read-otp.php` command above. Instead of that code, try `000000`. **Expected in lab mode:** the site signs you in. To check the missing attempt limit, sign in again and submit an incorrect six-digit value at least six times before entering `000000`; verification still succeeds. The code `000000` is never issued as a normal code. With `APP_LAB_MODE=0`, the fixed code fails, five failed attempts end the pending challenge, and a newly issued valid code succeeds once.

## Account statement access

Sign in as customer 1 and open a statement from `/dashboard`. The URL contains `?account=1` or `?account=2`. Change it to `/statement?account=3`. **Expected:** customer 2's fictional account holder and statement appear. With lab mode off, the same URL returns 404.

## Branch search SQL injection

Open `/locations` and use **Search branches**. A normal city such as `Colombo` returns one result. Search for `' OR 1=1 #`. **Expected:** every fictional branch appears because the city input changes the SQL predicate. With lab mode off, input is bound as data and no branches match that text.

## Offer search reflected XSS

Open `/offers` and use **Search card offers**. Search for `<img src=x onerror=alert(1)>`. **Expected in lab mode:** the search results page inserts the query as HTML and the browser runs the event handler. The original offer cards and browser category filter are separate; this exercise is in the server search results at `/offers/search`. With lab mode off, the same text is escaped and inline scripts are blocked.

## Application status tampering

Sign in as a fictional customer and open `/apply/account`. The form does not show a status field. Before submitting a valid application, use browser developer tools or an intercepting proxy to add `status=reviewed` to the POST body. **Expected in lab mode:** the new application appears as **Reviewed** in the customer dashboard without staff action. A normal submission remains **Pending**. With lab mode off, the extra field is ignored and every new application starts pending. The same client-controlled field also affects `/apply/card`.

## Digital banking open redirect

Open `/digital` and follow **Go to online banking**; it normally sends you to `/login` through `/digital/continue?next=/login`. Change `next` to `https://example.org/serendib-check` and inspect the response's `Location` header. **Expected in lab mode:** the response redirects to that external address. With lab mode off, external destinations are replaced with `/login`. This route redirects the browser only; the server does not fetch the destination.

## Repeat-transfer CSRF

Sign in as customer 1 and use **Repeat transfer** on `/dashboard`; it moves LKR 100.00 between that customer's two fictional accounts. Revisit the resulting `/transfer/repeat?from=1&to=2&amount=100.00` URL directly or open it as a link from another page. **Expected:** each GET changes balances without a form confirmation or CSRF token. Normal `/transfer` remains a POST form with CSRF protection. With lab mode off, `/transfer/repeat` returns 404. Browser cookie settings affect cross-site delivery; the local check proves the unsafe state-changing GET.

## Assistant handoff stored XSS

Sign in as customer 1, open **Chat with us**, and send `<script>alert(1)</script>`. Open **View conversation** and select **Send to our team**. Then sign in as staff and open `/staff`. **Expected:** the escalated assistant transcript renders as HTML in the staff workspace and the script executes. A regular `/support/request` message is still escaped. With lab mode off, escalated chat text is escaped and inline scripts are blocked by CSP.

## Reset and boundaries

To restore fictional customer balances locally, stop and remove the root Compose database volume with `docker compose down -v`, then start again and recreate logins. This deletes the local fictional database and user accounts. Do not run that reset on a public VM. The site must remain on localhost until the [public VM plan](../docs/public-vm-plan.md) is completed.
