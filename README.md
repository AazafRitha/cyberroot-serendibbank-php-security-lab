# CyberRoot SerendibBank PHP Security Lab

![SerendibBank burgundy and gold lotus vault logo](public/assets/logo.svg)

SerendibBank is a fictional banking website for CyberRoot security training. This is a **new PHP/MySQL repository**; the earlier AazafRitha banking lab repository is untouched. The banking UI uses product and service language, while this repository documents the educational purpose.

The site has responsive product pages, a guided banking assistant, customer login with a six-digit verification step, account activity, own-account transfers, account and card applications, support requests, and a staff workspace. Fictional MySQL data is seeded; portal user accounts are created explicitly. With `APP_LAB_MODE=1`, the **main site itself** contains intentional weaknesses in login, OTP verification, account statements, branch search, repeat transfers, and the assistant-to-staff handoff. The older standalone exercises remain under `scenarios/` for focused practice.

## Run on your Windows laptop

Follow the [Docker on Windows guide](docs/docker-on-windows.md). Docker Desktop replaces the need for XAMPP. After Docker is running, from this folder:

```powershell
Copy-Item .env.example .env # first run only; keep an existing .env
docker compose up --build -d
```

Open <http://localhost:8080>. Change the local-only sample database passwords in `.env`; it is excluded from Git. To change the web port, edit `APP_PORT`. `APP_LAB_MODE=1` enables the intentional main-site weaknesses for local practice; set it to `0` and recreate the web container for the safer baseline. The schema seeds two fictional customers but **no login accounts**. Create accounts using the examples below, saving the one-time passwords in a secure place outside Git:

```powershell
docker compose exec web php scripts/create-user.php 'customer1@example.test' 'N. Perera' customer 1
docker compose exec web php scripts/create-user.php 'customer2@example.test' 'M. Fernando' customer 2
docker compose exec web php scripts/create-user.php 'staff@example.test' 'Service Officer' staff
```

Open `/login` and sign in with a generated password. The local lab delivers each five-minute OTP to an operator-only Docker outbox rather than a real email provider. In another PowerShell window, read the latest code for the account you are testing:

```powershell
docker compose exec web php scripts/read-otp.php 'customer1@example.test'
```

Enter that code at `/verify`. The code is removed from the outbox after successful verification. The intentionally weak lab-mode verification behavior is documented in the [main-site validation guide](validation/main-site.md#otp-verification-bypass). To reset an account password and invalidate its old sessions:

```powershell
docker compose exec web php scripts/reset-password.php 'customer1@example.test'
```

Stop with `docker compose down` to preserve the local MySQL volume. **Only for disposable local data**, `docker compose down -v` removes that volume and replays `database/init.sql` on the next start. Schema edits do not update an existing volume; use a migration for data you need to keep.

Open [design/home.html](design/home.html) to inspect the static public-page preview without Docker. Refresh it after editing marketing views with `powershell -ExecutionPolicy Bypass -File scripts/build-preview.ps1`. Login, forms, dashboards, and the live assistant require the PHP/MySQL app.

## Structure and validation

```text
app/          MVC controllers, models, views, and authentication
public/       Apache web root and shared assets
database/     Fictional schema and seed data
docker/       Apache configuration
scripts/      Account provisioning, password reset, static preview
scenarios/    Standalone versions of the exercises
validation/   Main-site and standalone reproduction steps
docs/         Architecture, Docker, Git, and deployment planning
```

Start with the [main-site validation guide](validation/main-site.md), then use the [validation matrix](validation/README.md) for the standalone versions. The [page and scenario map](docs/page-and-scenario-map.md) explains why each weakness belongs in its banking area. The [architecture notes](docs/architecture.md) describe the mode switch and controls.

## GitHub and deployment

The new repository is [AazafRitha/cyberroot-serendibbank-php-security-lab](https://github.com/AazafRitha/cyberroot-serendibbank-php-security-lab). Use the [Git workflow](docs/git-workflow.md) for later changes. Never commit `.env`, generated passwords, or real customer data.

The Compose stack binds the website to `127.0.0.1` and leaves MySQL and the OTP outbox unexposed. It is **not** a public-VM configuration, especially with `APP_LAB_MODE=1`. Follow the [public VM plan](docs/public-vm-plan.md) for TLS, code delivery, account isolation, rules of engagement, logging, backups, reset, and challenge isolation. Do not expose it publicly by changing the port binding alone.
