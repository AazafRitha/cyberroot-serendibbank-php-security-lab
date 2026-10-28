# SerendibBank

![SerendibBank logo](public/assets/logo.svg)

SerendibBank is a PHP and MySQL website with public product pages, product information documents, a savings calculator, branch map, general enquiry form, and an online banking portal. It includes customer registration, sign in, verification, account activity, transfers, account and card applications, customer account settings, support requests with attachments, a digital assistant, and a separate operations workspace for staff, administrators, and super administrators. The layout works across desktop and mobile screens.

This repository is for authorized security learning. See the [security testing guide](docs/security-testing-guide.md) for the finding inventory and validation steps. The site itself presents normal banking journeys.

## Run locally

Install Docker Desktop and follow the [Windows setup guide](docs/docker-on-windows.md). For the short routine to use each day, see [Start and stop the site](docs/docker-on-windows.md#4-every-day-start-work-and-stop). On the first run only, open this repository in PowerShell and run:

```powershell
Copy-Item .env.example .env # first run only; keep an existing .env
docker compose up --build -d
docker compose ps
```

Open the site on the port in `.env`: `APP_PORT=8080` gives <http://127.0.0.1:8080>, while `APP_PORT=8082` gives <http://127.0.0.1:8082>. This laptop uses 8082 because Burp Suite is listening on 8080. Edit `.env` to set local database passwords. New customers can register at `/register` with a name, email, NIC or passport number, address, and password. Registration assigns a unique account number and creates an empty LKR everyday account; the customer then signs in and verifies the code. Use fictional identity and address details for this project. The initial database also includes two customer records and sample account activity, but no sign-in accounts for those records. To test the seeded account activity and two-customer scenarios, create portal users with:

```powershell
docker compose exec web php scripts/create-user.php 'customer1@example.test' 'N. Perera' customer 1
docker compose exec web php scripts/create-user.php 'customer2@example.test' 'M. Fernando' customer 2
docker compose exec web php scripts/create-user.php 'staff@example.test' 'Service Officer' staff
```

Save the generated passwords outside Git. Sign in at `/login`. For local development, verification codes are delivered to a Docker volume; read the latest code with:

```powershell
docker compose exec web php scripts/read-otp.php 'customer1@example.test'
```

To reset a password, run `docker compose exec web php scripts/reset-password.php 'customer1@example.test'`. Use `docker compose down` to stop the containers while keeping MySQL data. `docker compose down -v` removes local database data and the identity fingerprint key; it rebuilds the seed data on the next start. Back up the `identity_keys` volume together with the database if you need to restore registrations.

The laptop uses a local verification-code outbox by default. To send sign-in codes to the account's email address, configure the company SMTP server using the [email and management setup guide](docs/email-and-management.md). The management workspace runs on a separate hostname, `manage.localhost` locally. A VM can use a management subdomain through Caddy. Bootstrap the first super administrator with `docker compose exec web php scripts/create-user.php 'superadmin@example.test' 'Operations Director' super_admin`; replace the example address with an inbox you control and save the generated password privately. That account can create staff and admin users in the management workspace.

If you use Burp Suite with the local site, follow the [Windows Burp setup](docs/burp-on-windows.md). The site and Burp need different ports. For example, keep Burp's default proxy on 8080 and set `APP_PORT=8082` in `.env`, or keep the site on 8080 and move Burp to 8081.

## Project layout

```text
app/       Controllers, models, views, and sessions
public/    Apache web root and browser assets
database/  Schema and initial records
docker/    Apache configuration
scripts/   User administration and static preview generation
tests/     Portal and settings workflow checks
docs/      Setup, architecture, and Git workflow
```

The [architecture notes](docs/architecture.md) describe the application structure. The [Git workflow](docs/git-workflow.md) covers local changes and pushing them to GitHub. SQL files in `database/migrations/` are applied when the web container starts, including on an existing database volume. `.env`, generated passwords, and customer records belong outside Git.

The included Compose file binds the website to `127.0.0.1` for local development. A public server needs a separate deployment configuration, TLS, operational monitoring, backups, and a real verification delivery service.

For an isolated Ubuntu VM, follow the [Ubuntu VM deployment guide](docs/ubuntu-vm-deployment.md) and use `compose.vm.yaml` instead of the laptop Compose file.

Registration stores a keyed fingerprint and last four characters of the normalized identity number rather than the full number. The address is stored in the database. The fingerprint key lives in a separate Docker volume and must remain paired with its database. This local flow does not verify a real person or document, so do not use real identity information or treat it as a public banking onboarding process.
