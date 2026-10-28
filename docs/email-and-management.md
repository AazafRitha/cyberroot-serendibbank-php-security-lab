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

The workspace has its own sign-in and code verification. Staff users see operational totals and service queues. Administrators also see the user directory, search, role controls, and account suspension controls. Changes to a user's role or active status invalidate that user's existing session. The original `/staff` route remains available for the existing local workflows.

Create the first administrator from a trusted VM terminal, replacing the example address with the administrator's real inbox:

```bash
sudo docker compose -f compose.vm.yaml exec web php scripts/create-user.php 'admin@yourdomain.example' 'Operations Manager' admin
```

The generated password is shown once. Save it privately. The account can then sign in at the management subdomain and receive its code by company SMTP.

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
