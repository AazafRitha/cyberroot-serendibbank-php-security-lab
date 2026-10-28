# First run with Docker on Windows

This guide starts from a Windows laptop without Docker or WSL. You do **not** need XAMPP, a separate PHP installation, or a separate MySQL installation for this project.

## 1. Install WSL 2

Open **PowerShell as Administrator** and run:

```powershell
wsl --install
```

Restart Windows if asked. After the restart, open a new PowerShell window and run `wsl --version`. It should print version details. If installation fails, use the [Microsoft WSL installation guide](https://learn.microsoft.com/windows/wsl/install) and check that virtualization is enabled in your PC's firmware.

Docker Desktop's current Windows requirements include WSL 2, hardware virtualization, and at least 8 GB RAM. Check the [official system requirements](https://docs.docker.com/desktop/setup/install/windows-install/) before installing it.

## 2. Install Docker Desktop

Download Docker Desktop from its [official Windows installation page](https://docs.docker.com/desktop/setup/install/windows-install/). Choose the regular per-user installation and the **WSL 2** backend. Restart if prompted, then start **Docker Desktop** from the Start menu. Wait until its engine shows as running.

Open a **new** PowerShell window and check:

```powershell
docker --version
docker compose version
docker info
```

The first two commands should show versions. `docker info` should show server information. If it says it cannot connect to the Docker daemon, start Docker Desktop and wait for the engine to finish starting.

## 3. Start SerendibBank

In PowerShell:

```powershell
Set-Location 'C:\Users\aazaf\OneDrive\Documents\ChatGPT\Cyber Root\cyberroot-serendibbank-php-security-lab'
Copy-Item .env.example .env
docker compose config
docker compose up --build -d
docker compose ps
```

Run `Copy-Item` only the first time; do not overwrite your existing `.env` later. `docker compose config` checks and expands the Compose configuration. The first build downloads the PHP and MySQL images, so it may take several minutes. Wait until the `db` container is healthy and `web` is running. Then open <http://localhost:8080>.

Docker Compose reads `compose.yaml`: it builds the PHP/Apache **web** container, starts the MySQL **db** container, creates a private network for the database, and creates a named volume that keeps database data after containers stop. The website's port is available only at `127.0.0.1` on this laptop.

The public site works immediately. To use online banking, create customer and staff logins with the commands in the [README](../README.md#run-locally). Save their generated passwords securely outside the repository, then open <http://localhost:8080/login>.

## 4. Every day: start, work, and stop

Think of Docker Desktop as the engine and this project's two containers as the running site: `web` runs PHP and Apache, while `db` stores MySQL data. You do not need XAMPP.

**When you start work:**

1. Open **Docker Desktop** from the Windows Start menu. Wait until it says the engine is running.
2. Open the SerendibBank folder in VS Code. Open **Terminal → New Terminal** (PowerShell). If your terminal opens somewhere else, move to the project folder:

   ```powershell
   Set-Location 'C:\Users\aazaf\OneDrive\Documents\ChatGPT\Cyber Root\cyberroot-serendibbank-php-security-lab'
   ```

3. Start the site and check its status:

   ```powershell
   docker compose up -d
   docker compose ps
   ```

   Expect `web` to be **Up** and `db` to be **Up (healthy)**. Open the website on the `APP_PORT` set in `.env`. This laptop currently uses <http://127.0.0.1:8082/> because Burp Suite uses port 8080. Refresh the page after editing PHP, CSS, or JavaScript files; the project folder is shared with the web container.

**When you finish work:** run `docker compose down` in the same folder. This stops the site and keeps your database and accounts for the next day. You can then close Docker Desktop.

**When something fails:** run `docker compose logs --tail=50 web` for site errors or `docker compose logs --tail=50 db` for database errors. `docker compose logs -f web` shows new site log lines live; press **Ctrl+C** to leave the log view without stopping the containers. If Docker cannot connect, check that Docker Desktop is running.

Use `docker compose up -d --build` after changing the Dockerfile or Compose setup. You do **not** need to rebuild for ordinary PHP, CSS, or JavaScript edits. `Copy-Item .env.example .env` is a first-run step only; keep your existing `.env` and passwords.

**Only when you intend to erase local database data:** `docker compose down -v` removes the MySQL and identity key volumes. The seeded records return on the next start, but accounts, requests, and registration identity fingerprints you created are lost.

## If something fails

| What you see | First check |
| --- | --- |
| `wsl` is missing or cannot start | Finish the WSL installation, restart, and check virtualization in Windows Task Manager → Performance → CPU. |
| `docker` is not recognized | Finish installing Docker Desktop, then open a new PowerShell window. |
| Cannot connect to the Docker daemon | Start Docker Desktop and wait for its engine to run. |
| Port 8080 is already in use | Change `APP_PORT=8080` to another free port in `.env`, then restart Compose. |
| Website returns a database error | Check `docker compose ps` and `docker compose logs --tail=50 db`. The first MySQL initialization can take a little time. |
| Changes to `database/init.sql` do not appear | The SQL file runs only when a fresh database volume is created. Use a migration or deliberately reset the local volume. |

If the first run still fails, save the exact error from `docker compose up --build -d` and the recent logs. Those messages identify the next fix.
