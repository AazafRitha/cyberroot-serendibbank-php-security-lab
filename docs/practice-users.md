# Prepare fictional practice accounts

The operator can add 12 customer profiles with two accounts and sample transactions each, five staff users, two administrators, and one super administrator. The script leaves existing users untouched. Every newly created login receives a different random password. All names, addresses, identity references, and account numbers are fictional. The email addresses use the reserved `.test` domain.

Run this **on the Ubuntu VM**, after the web and database containers are up. The credential CSV is written in your home directory, outside the Git checkout. Keep it private and do not send the complete file to learners.

```bash
cd ~/cyberroot-serendibbank-php-security-lab
git pull --ff-only
sudo docker compose -f compose.vm.yaml up -d --build
umask 077
credentials_file=$(mktemp --suffix=.csv "$HOME/practice-credentials.XXXXXX")
sudo docker compose -f compose.vm.yaml exec -T web php scripts/seed-practice-users.php > "$credentials_file"
echo "Credentials saved to: $credentials_file"
sudo docker compose -f compose.vm.yaml exec -T db sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot "$MYSQL_DATABASE" -N -e "SELECT role, COUNT(*) FROM users GROUP BY role"'
```

The first run creates up to 20 users. An existing email is skipped; rerunning the command does not reset its password or duplicate its customer. **Keep the first credentials file**: passwords for skipped users cannot be recovered. The CSV contains each new user's role, email, one-time password, customer ID, and account numbers. To inspect it privately, use `head -n 5 "$credentials_file"`. Do not commit it or paste it into a public report.

These `.test` mailboxes cannot receive sign-in codes. With `MAIL_TRANSPORT=outbox`, the operator can retrieve a code for a selected practice account:

```bash
sudo docker compose -f compose.vm.yaml exec web php scripts/read-otp.php 'practice.customer01@example.test'
```

For independent remote sign-in, configure SMTP and create users with email addresses they control. See [email and management setup](email-and-management.md). Keep the management workspace on its own hostname; the temporary customer-site Quick Tunnel does not publish it.

The repository documents the testing purpose. Distribute credentials only to authorized participants, use separate accounts for each participant, and restore the VM from a snapshot when resetting the exercise.

## View the full live user directory

The [practice user directory workbook](practice-user-directory.xlsx) lists the 20 accounts defined by the seed script, arranged by role. It includes their fictional seed profile details and initial account settings. It contains no passwords and is a fixture reference, not a live database export. The Ubuntu VM may also contain users created through registration or management.

For an operator's private workbook, combine the live directory export with the original credential CSV from the same machine. The seed creates random passwords separately on each server, and existing password hashes cannot be reversed. Keep any workbook containing passwords in an ignored `private/` folder, outside shared reports and repository commits. The workbook on one machine does not contain the passwords generated on another machine.

To export **all current VM users** privately, including their role, status, account numbers, address, identity type and document's last four characters, run this on Ubuntu. The CSV has no passwords, password hashes, full identity numbers or OTPs, but it contains personal details, so keep it outside Git and share it only with operators.

```bash
cd ~/cyberroot-serendibbank-php-security-lab
umask 077
directory_file=$(mktemp --suffix=.csv "$HOME/user-directory-live.XXXXXX")
sudo docker compose -f compose.vm.yaml exec -T web php scripts/export-user-directory.php > "$directory_file"
echo "Private live directory: $directory_file"
```

Open that CSV in Excel and filter the `role` column, or make a private copy of the workbook for the VM's current records. The repository workbook does not update automatically when users register.
