# Public VM deployment plan

SerendibBank is an educational security project, while the browser-facing banking experience should read as an ordinary banking product. Keep the educational purpose, authorization, and exercise details in the repository and a separate program scope document; do not place lab labels in the banking UI.

The current Compose file is for **local development**. Its HTTP port is bound to `127.0.0.1`, it bind-mounts source code from the laptop, and the app reads one fixed customer record without authentication. It is not ready to publish on the VM.

## Before public access

1. Build authentication, session handling, and account authorization. Give each learner separate fictional accounts and data. The fixed customer ID must be removed.
2. Define the permitted targets, test methods, reporting channel, reset schedule, and prohibited actions in a separate rules-of-engagement document that participants can read before testing.
3. Run the application in a dedicated VM with a separate database network and no real banking systems, identities, or secrets. Limit management access to operators.
4. Put a reverse proxy and TLS in front of the web container. Publish only the intended HTTP/HTTPS entry point. Keep MySQL unexposed and do not mount editable source code into the public container.
5. Add request logs, abuse controls, backups or snapshots, a repeatable reset process, and a response plan for unexpected behavior.
6. Test the deployed configuration and recovery process on a staging VM before announcing the target.

Keep intentionally vulnerable scenarios explicit and isolated from the core app. Do not enable a scenario globally until its impact and reset behavior have been reviewed. A public challenge environment needs its own deployment configuration; changing only the host port in `compose.yaml` does not complete this plan.
