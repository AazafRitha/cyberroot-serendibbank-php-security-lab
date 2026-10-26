# Public VM deployment plan

SerendibBank is an educational security project while the browser-facing experience reads as a banking site. Keep the authorization, reporting process, and exercise details in the repository and a separate participant scope document.

The current Compose stack is **local development only**: its HTTP port binds to `127.0.0.1`, it bind-mounts source code from the laptop, and `APP_LAB_MODE=1` exposes intentional weaknesses on the main site. It is not a public deployment configuration.

Before public access:

1. Give every learner separate fictional accounts, rotate generated credentials, and add an operator process for account lifecycle and recovery. Replace the local Docker OTP outbox with a private delivery channel before inviting participants; never publish the outbox or its reader script as a web route.
2. Publish written rules of engagement: authorized hostnames, test methods, reporting channel, rate limits, reset schedule, prohibited actions, and a clear separation from real banks.
3. Deploy to a dedicated VM, with no real banking systems, identities, or secrets. Restrict management access and keep MySQL off public ports.
4. Put a reverse proxy and TLS in front. Set `APP_HTTPS=1` so session cookies are Secure. Publish only the intended HTTPS endpoint and remove the development source bind mount.
5. Add request and audit logs, abuse controls, backups/snapshots, a repeatable fictional-data reset, and a response plan.
6. Test staging, recovery, access controls, and deployment configuration before announcing the target. Review each intended weakness's impact, scope, and reset behavior. Keep the challenge VM isolated from real systems and data.

Do not publish the current Compose stack by changing its port binding. Its lab mode deliberately weakens authentication, accepts external redirects, renders untrusted HTML, and exposes fictional customer data through several flows. A public challenge needs its own deployment configuration, participant scope, monitoring, and recovery process.
