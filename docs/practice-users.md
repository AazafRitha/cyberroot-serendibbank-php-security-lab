# Fictional user dataset

The operator's single CSV file is `private/practice-users.csv`. It defines 72 fictional identities: 50 customers, 15 staff, five administrators, and two super administrators. Every row has a unique email and a different, easy-to-guess password derived from that person's first name and record number. Customer rows include a synthetic passport reference, address, and two account numbers. The `.test` email domains cannot reach real people.

The CSV maps 24 previous logins through `legacy_email`; the importer updates those users instead of duplicating them. It also attaches the new customer login for the second initial customer profile. Existing balances and transaction history are preserved. New customer accounts receive opening balances and a few sample transactions. Repeating the import is safe: existing users and accounts are matched by their current email and account type.

The `private/` folder is ignored by Git. Keep the full CSV with the operator and copy it to each VM through a private channel. Do not add it to GitHub. New user registrations are not automatically added to this dataset.

## Windows laptop

From the project folder in PowerShell, after placing the CSV in `private/`:

```powershell
docker compose up -d --build
docker compose exec -T web php scripts/import-practice-users.php --validate
docker compose exec -T web php scripts/import-practice-users.php
```

The first command starts the site. The second checks the CSV without changing the database. The third applies it, reporting added and updated users. Run it again after restoring an older database snapshot. The import changes the named users' passwords and invalidates their previous sessions. It does not change passwords for unrelated registrations.

The older `scripts/seed-practice-users.php` command must not be used alongside this dataset; it would recreate the earlier placeholder emails. It exits with a reminder when the private CSV is present.

## Ubuntu VM

Pull the code and copy `practice-users.csv` from the laptop to `~/cyberroot-serendibbank-php-security-lab/private/practice-users.csv` on the VM. The CSV is intentionally absent from GitHub. Use a VMware shared folder or SCP once SSH access is working. Do not paste passwords into chat or a public terminal transcript.

Then run on Ubuntu:

```bash
cd ~/cyberroot-serendibbank-php-security-lab
git pull --ff-only
mkdir -p private
chmod 700 private
chmod 600 private/practice-users.csv
sudo docker compose -f compose.vm.yaml up -d --build
sudo docker compose -f compose.vm.yaml exec -T web php scripts/import-practice-users.php --validate
sudo docker compose -f compose.vm.yaml exec -T web php scripts/import-practice-users.php
sudo docker compose -f compose.vm.yaml ps
```

The VM may have different user IDs, balances, and activity from the laptop. The import aligns the named accounts, customer profiles, account numbers, and passwords to the CSV while keeping existing balances and transaction history.

The `.test` mailboxes cannot receive sign-in codes. With `MAIL_TRANSPORT=outbox`, the operator can retrieve a code for a selected dataset email:

```bash
sudo docker compose -f compose.vm.yaml exec web php scripts/read-otp.php 'EMAIL_FROM_PRIVATE_CSV'
```

For independent remote sign-in, configure SMTP and use addresses participants control. See [email and management setup](email-and-management.md). The temporary customer-site Quick Tunnel does not publish the management workspace on its separate hostname.
