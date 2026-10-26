# SerendibBank

![SerendibBank logo](public/assets/logo.svg)

SerendibBank is a PHP and MySQL website with public product pages and an online banking portal. It includes customer sign in, verification, account activity, transfers, account and card applications, support requests with attachments, a digital assistant, and a staff workspace. The layout works across desktop and mobile screens.

## Run locally

Install Docker Desktop and follow the [Windows setup guide](docs/docker-on-windows.md). In PowerShell, open this repository and run:

```powershell
Copy-Item .env.example .env # first run only; keep an existing .env
docker compose up --build -d
docker compose ps
```

Open <http://localhost:8080>. Edit `.env` to set local database passwords and change `APP_PORT` if port 8080 is occupied. The initial database includes two customer records and sample account activity, but no sign-in accounts. Create portal users with:

```powershell
docker compose exec web php scripts/create-user.php 'customer1@example.test' 'N. Perera' customer 1
docker compose exec web php scripts/create-user.php 'customer2@example.test' 'M. Fernando' customer 2
docker compose exec web php scripts/create-user.php 'staff@example.test' 'Service Officer' staff
```

Save the generated passwords outside Git. Sign in at `/login`. For local development, verification codes are delivered to a Docker volume; read the latest code with:

```powershell
docker compose exec web php scripts/read-otp.php 'customer1@example.test'
```

To reset a password, run `docker compose exec web php scripts/reset-password.php 'customer1@example.test'`. Use `docker compose down` to stop the containers while keeping MySQL data. `docker compose down -v` removes local database data and rebuilds it from `database/init.sql` on the next start.

## Project layout

```text
app/       Controllers, models, views, and sessions
public/    Apache web root and browser assets
database/  Schema and initial records
docker/    Apache configuration
scripts/   User administration and static preview generation
tests/     Basic portal workflow checks
docs/      Setup, architecture, and Git workflow
```

The [architecture notes](docs/architecture.md) describe the application structure. The [Git workflow](docs/git-workflow.md) covers local changes and pushing them to GitHub. `.env`, generated passwords, and customer records belong outside Git.

The included Compose file binds the website to `127.0.0.1` for local development. A public server needs a separate deployment configuration, TLS, operational monitoring, backups, and a real verification delivery service.
