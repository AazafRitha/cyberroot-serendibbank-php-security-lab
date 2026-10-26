# Public VM deployment plan

SerendibBank is an educational security project while the browser-facing experience reads as a banking site. Keep the authorization, reporting process, and exercise details in the repository and a separate participant scope document.

The current Compose stack is **local development only**: its HTTP port binds to `127.0.0.1` and it bind-mounts source code from the laptop. The core now has customer and staff login, role separation, CSRF checks, customer-scoped data, and working request flows. Those changes do not make this Compose file a public deployment.

Before public access:

1. Give every learner separate fictional accounts, rotate generated credentials, add an operator process for account lifecycle and recovery, and consider MFA for staff.
2. Publish written rules of engagement: authorized hostnames, test methods, reporting channel, rate limits, reset schedule, prohibited actions, and a clear separation from real banks.
3. Deploy to a dedicated VM, with no real banking systems, identities, or secrets. Restrict management access and keep MySQL off public ports.
4. Put a reverse proxy and TLS in front. Set `APP_HTTPS=1` so session cookies are Secure. Publish only the intended HTTPS endpoint and remove the development source bind mount.
5. Add request and audit logs, abuse controls, backups/snapshots, a repeatable fictional-data reset, and a response plan.
6. Test staging, recovery, access controls, and deployment configuration before announcing the target. Review each intended vulnerable challenge's impact separately and isolate it from the core.

The five local scenario stacks must stay off the public VM until a dedicated challenge deployment and scope are approved. Changing a port in `compose.yaml` is insufficient.
