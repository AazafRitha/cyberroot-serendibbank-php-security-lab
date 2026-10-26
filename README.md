# CyberRoot SerendibBank PHP Security Lab

SerendibBank is a fictional banking demo for CyberRoot security training. This is a **new PHP/MySQL project** with its own Git history. It does not replace the earlier React/Node repository.

The starter app contains a read-only account dashboard backed by fictional MySQL data. The public-facing pages are written as a banking site; the project purpose is documented here. There is no login, money movement, or real customer information yet. Security exercises belong in separate scenario folders and container stacks.

## What is included

- PHP 8.3, Apache, and a small MVC structure with no Composer dependencies
- MySQL 8.4 with fictional seed data
- A local-only Docker Compose stack; MySQL has no host port
- Prepared PDO queries and escaped HTML in the core demo
- An isolated scenario template for future PHP advisories or application flaws

## Learn Docker and run locally

Start with the [Windows Docker walkthrough](docs/docker-on-windows.md), which covers WSL 2, Docker Desktop, the first run, and troubleshooting. From this folder, after Docker Desktop is running:

```powershell
Copy-Item .env.example .env
docker compose up --build -d
```

Open <http://localhost:8080> and select **View accounts**. To change the web port, edit `APP_PORT` in `.env`.

Stop the lab with `docker compose down`. This keeps the MySQL volume. `docker compose down -v` also removes the fictional database so it can be initialized again on the next run.

The sample passwords in `.env.example` are for a local lab only. Change them in `.env`; that file is excluded from Git. If you change database initialization settings after the first run, recreate the local database volume or update the database manually.

## Project layout

```text
app/
  Controllers/     Request handlers
  Core/            Router, view renderer, PDO connection
  Models/          Database reads
  Views/           PHP templates
public/            Only web-exposed directory
database/          Demo schema and seed data
docker/            Apache configuration
docs/              Architecture, scenarios, and Git workflow
scenarios/         Independent exercise template
```

## Create the new GitHub repository

The GitHub connection available to this workspace cannot create repositories. The project is prepared locally; create an **empty new repository** in GitHub:

1. In GitHub, select **+** → **New repository**.
2. Choose the intended owner, and name it `cyberroot-serendibbank-php-security-lab`.
3. Choose **Private** while developing the lab. Add a short description such as “CyberRoot fictional PHP/MySQL banking security lab.”
4. Leave **Add a README**, **Add .gitignore**, and **Choose a license** unselected, then select **Create repository**.
5. In this local folder, run the commands below, replacing `OWNER` if needed:

```powershell
git remote add origin https://github.com/OWNER/cyberroot-serendibbank-php-security-lab.git
git push -u origin main
```

GitHub may ask you to authenticate. These commands target the new URL only. See [docs/git-workflow.md](docs/git-workflow.md) for the ongoing workflow.

## Public VM plan

The local Compose stack is not a public deployment. The current accounts page reads one fixed customer record without authentication. Before exposing the site, implement separate learner accounts and follow the [public VM plan](docs/public-vm-plan.md). The banking UI contains no lab or demo wording; the repository and program scope documents describe its educational use.

## Security lab boundary

Run this only on a computer or network you control. The Compose web port binds to `127.0.0.1`, and the database is only on the project network. Keep any intentionally vulnerable exercise inside its own scenario folder and stack. Do not put real credentials or customer data in this repository.
