# CyberRoot SerendibBank PHP Security Lab

![SerendibBank burgundy and gold lotus vault logo](public/assets/logo.svg)

SerendibBank is a fictional banking website for CyberRoot security training. This is a **new PHP/MySQL repository**; the earlier AazafRitha banking lab repository is untouched. The banking UI uses product and service language, while this repository documents the educational purpose.

The core site has responsive public product pages, customer login, a customer dashboard with account activity, own-account transfers, account and card applications, support requests, and a staff workspace for review and resolution. Fictional MySQL data is seeded; portal user accounts are created explicitly. Five deliberately vulnerable OWASP exercises run in separate localhost-only stacks under `scenarios/`.

## Run on your Windows laptop

Follow the [Docker on Windows guide](docs/docker-on-windows.md). Docker Desktop replaces the need for XAMPP. After Docker is running, from this folder:

```powershell
Copy-Item .env.example .env
docker compose up --build -d
```

Open <http://localhost:8080>. Change the local-only sample database passwords in `.env`; it is excluded from Git. To change the web port, edit `APP_PORT`. The schema seeds two fictional customers but **no login accounts**. Create accounts using the examples below, saving the one-time passwords in a secure place outside Git:

```powershell
docker compose exec web php scripts/create-user.php 'customer1@example.test' 'N. Perera' customer 1
docker compose exec web php scripts/create-user.php 'customer2@example.test' 'M. Fernando' customer 2
docker compose exec web php scripts/create-user.php 'staff@example.test' 'Service Officer' staff
```

Open `/login` and sign in with a generated password. To reset an account password and invalidate its old sessions:

```powershell
docker compose exec web php scripts/reset-password.php 'customer1@example.test'
```

Stop with `docker compose down` to preserve the local MySQL volume. **Only for disposable local data**, `docker compose down -v` removes that volume and replays `database/init.sql` on the next start. Schema edits do not update an existing volume; use a migration for data you need to keep.

Until Docker is available, open [design/home.html](design/home.html) to inspect the static public-page preview. Refresh it after editing marketing views with `powershell -ExecutionPolicy Bypass -File scripts/build-preview.ps1`. Login, forms, and dashboards require the live PHP/MySQL app.

## Structure and validation

```text
app/          MVC controllers, models, views, and authentication
public/       Apache web root and shared assets
database/     Fictional schema and seed data
docker/       Apache configuration
scripts/      Account provisioning, password reset, static preview
scenarios/    Independent, intentionally vulnerable local exercises
validation/   Reproduction steps and secure-core checks
docs/         Architecture, Docker, Git, and deployment planning
```

Use the [validation matrix](validation/README.md) to check each exercise. The [page and scenario map](docs/page-and-scenario-map.md) explains why each form and scenario belongs in its banking area. The [architecture notes](docs/architecture.md) describe the core controls.

## GitHub and deployment

The new repository is [AazafRitha/cyberroot-serendibbank-php-security-lab](https://github.com/AazafRitha/cyberroot-serendibbank-php-security-lab). Use the [Git workflow](docs/git-workflow.md) for later changes. Never commit `.env`, generated passwords, or real customer data.

The Compose stack binds the website to `127.0.0.1` and leaves MySQL unexposed. It is **not** a public-VM configuration. Follow the [public VM plan](docs/public-vm-plan.md) for TLS, account isolation, rules of engagement, logging, backups, reset, and challenge isolation. Do not expose the vulnerable scenario stacks by changing their port bindings.
