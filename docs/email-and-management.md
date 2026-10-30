# Email delivery and management workspace

## Send codes to the account email

The sign-in flow generates a six-digit code after a correct password. In `MAIL_TRANSPORT=smtp` mode, PHPMailer sends it to the email address loaded from that user account. The code expires after five minutes. If delivery fails, sign-in stops with an error rather than pretending the code was sent.

Ask the company mail administrator for the SMTP hostname, port, encryption method, sending account, and an approved From address. Keep credentials in the VM's ignored `.env` file. Do not put them in Git, chat, or screenshots. The usual STARTTLS configuration looks like this; replace every example with values supplied by the administrator:

```dotenv
MAIL_TRANSPORT=smtp
MAIL_FROM_ADDRESS=security@yourdomain.example
MAIL_FROM_NAME=SerendibBank
SMTP_HOST=smtp.yourdomain.example
SMTP_PORT=587
SMTP_ENCRYPTION=starttls
SMTP_USERNAME=your-smtp-account
SMTP_PASSWORD=your-private-smtp-password
```

`SMTP_ENCRYPTION=smtps` is available for servers that require implicit TLS, often on port 465. `none` is only for a local test mail catcher. The From address must be permitted by your SMTP server. Arrange SPF, DKIM, and DMARC records with your mail administrator for reliable delivery. The VM also needs outbound access to the SMTP server.

This configuration supports SMTP password authentication or a trusted relay. If your company permits only OAuth-based mail submission, the mail integration needs that provider's OAuth setup before live delivery.

After changing `.env`, recreate the web container:

```bash
sudo docker compose -f compose.vm.yaml up -d --no-deps --force-recreate web
```

Test with a fictional account attached to an inbox you control. Sign in with its password, then confirm the code arrives in that inbox and opens the intended account. If mail fails, inspect `sudo docker compose -f compose.vm.yaml logs --tail=80 web` and verify the SMTP host, port, encryption, credentials, sender permission, and outbound firewall. The application deliberately does not log OTP values or SMTP passwords.

On the laptop, `.env` can retain `MAIL_TRANSPORT=outbox` for offline development. The code is then read with `docker compose exec web php scripts/read-otp.php 'email@example.test'`. To test SMTP locally without sending real mail, start the optional Mailpit profile and configure `SMTP_HOST=mailpit`, `SMTP_PORT=1025`, `SMTP_ENCRYPTION=none`. Mailpit's private inbox is at `http://127.0.0.1:8025` on the laptop. It must not be exposed on the public VM.

## Management hostname and accounts

`MANAGEMENT_HOST` selects the separate operations site. On the laptop, its default is `manage.localhost`, so use <http://manage.localhost:8082/> when the bank website uses port 8082. If that hostname does not resolve on your machine, add `127.0.0.1 manage.localhost` to the Windows hosts file as an administrator. The public site remains on your normal hostname.

The workspace has its own sign-in and code verification. The role and task split is:

| Role | Workspace capabilities |
| --- | --- |
| Customer | Personal banking site only; own accounts, transfers, applications, and support. |
| Staff | Customer directory, account and transaction search, service queues, transaction flags, and preparation of ledger credit/debit requests. |
| Admin | Staff capabilities, customer online-access suspension, account opening/freezing, transaction flag clearance, and approval or rejection of ledger requests prepared by another team member. |
| Super admin | Admin capabilities, internal staff and admin account creation, role assignment, staff access suspension, and the management activity log. |

Account opening creates a zero-balance LKR everyday or savings account for an existing client. A frozen account cannot be used in the normal or quick transfer flow. A ledger request never changes a balance until a different administrator approves it; approval posts one transaction and updates the account balance in one database transaction. A debit with insufficient funds is refused. These are operational training flows, not a complete core banking ledger or reconciliation system. Changes to a user's role or active status invalidate that user's existing session. The original `/staff` route remains available for the existing local workflows.

Customers can review and end their signed-in devices from **Account settings → Devices & sessions**. Staff can do the same from **My sessions** in the management workspace. A sign-in creates a server-side session record; ending a session marks that record revoked. The repository's [security testing guide](security-testing-guide.md) describes the intentional session-control finding. The website itself uses ordinary banking language.

Create the first super administrator from a trusted VM terminal, replacing the example address with the administrator's real inbox:

```bash
sudo docker compose -f compose.vm.yaml exec web php scripts/create-user.php 'superadmin@yourdomain.example' 'Operations Director' super_admin
```

The generated password is shown once. Save it privately. The account can then sign in at the management subdomain and receive its code by company SMTP. The super admin can create staff and admin accounts in **People & permissions**. A temporary password is displayed once in that session.

For a public VM, set `MANAGEMENT_HOST=manage.yourdomain.example` in `.env` and recreate the web container. Point both the bank and management DNS names to the VM. Add both names to Caddy's `/etc/caddy/Caddyfile`:

```caddyfile
bank.yourdomain.example {
    reverse_proxy 127.0.0.1:8082
}

manage.yourdomain.example {
    reverse_proxy 127.0.0.1:8082
}
```

Reload Caddy after validation. Only Caddy's HTTPS ports should be publicly reachable; the PHP container and MySQL stay private. The subdomain separates the interface, while account roles and server checks decide who may use it.
