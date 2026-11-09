# Practice accounts and private directory

The local operator workbook at `private/practice-user-directory.xlsx` contains the fictional accounts in the Windows laptop database, grouped by role. It includes the practice passwords and the available customer profile and account details. The `private/` folder is ignored by Git. This workbook is a laptop snapshot; it is not generated automatically on the Ubuntu VM.

The accounts use operator-chosen, simple passwords for password-guessing and password-reuse exercises. The passwords are stored in each server's private `.env` file, not in Git. The five-failure sign-in cooldown remains in place, and its `X-Forwarded-For` behavior is covered in the [security testing guide](security-testing-guide.md). Email verification is a separate step.

## Set up the Windows laptop

Add these four settings to the ignored `.env` file in the project folder. Enter your chosen practice passwords privately; use the same values on the VM if you want the workbook passwords to work there.

```dotenv
PRACTICE_PASSWORD_CUSTOMER=
PRACTICE_PASSWORD_STAFF=
PRACTICE_PASSWORD_ADMIN=
PRACTICE_PASSWORD_SUPER_ADMIN=
```

Then run in PowerShell:

```powershell
docker compose up -d --build
docker compose exec -T web php scripts/seed-practice-users.php > $null
docker compose exec -T web php scripts/sync-practice-passwords.php
```

The seed command creates up to 12 fictional customers, five staff users, two administrators, and one super administrator with random initial passwords. The synchronization command applies the private role passwords only to those 20 named practice accounts and four named original sample accounts if they exist. It leaves other registered users alone. Changing a password invalidates that user's existing sessions. Run the synchronization command after restoring an old database or resetting a practice password.

## Apply the same passwords on the Ubuntu VM

After pulling the GitHub update, add the four `PRACTICE_PASSWORD_*` entries to the VM's ignored `.env` file with the same values used on the laptop. Then run:

```bash
cd ~/cyberroot-serendibbank-php-security-lab
git pull --ff-only
sudo docker compose -f compose.vm.yaml up -d --build
sudo docker compose -f compose.vm.yaml exec -T web php scripts/seed-practice-users.php > /dev/null
sudo docker compose -f compose.vm.yaml exec -T web php scripts/sync-practice-passwords.php
sudo docker compose -f compose.vm.yaml ps
```

The four original sample accounts in the laptop workbook might not exist on the VM; the command reports absent accounts. Account numbers, balances, and timestamps are generated or updated separately on each server. The named practice accounts use the same passwords after synchronization.

The `.test` mailboxes cannot receive sign-in codes. With `MAIL_TRANSPORT=outbox`, the operator can retrieve a code for a selected practice account:

```bash
sudo docker compose -f compose.vm.yaml exec web php scripts/read-otp.php 'practice.customer01@example.test'
```

For independent remote sign-in, configure SMTP and create accounts with email addresses each participant controls. See [email and management setup](email-and-management.md). The temporary customer-site Quick Tunnel does not publish the management workspace on its separate hostname.

## Review a live VM directory

The laptop workbook does not refresh when users register. To export the current VM users privately, run:

```bash
cd ~/cyberroot-serendibbank-php-security-lab
sudo docker compose -f compose.vm.yaml exec -T web php scripts/export-user-directory.php > "$HOME/user-directory-live.csv"
```

The CSV includes names, emails, account details, address fields, and the last four characters of identity documents where available. It does not include passwords, full identity numbers, password hashes, or OTPs.
