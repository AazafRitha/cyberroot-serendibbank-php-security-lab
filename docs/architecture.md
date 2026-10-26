# Application architecture

Apache serves `public/` and routes requests to `public/index.php`. A small router connects public pages, customer and staff workflows, and JSON endpoints to controllers in `app/Controllers`. Models in `app/Models` read and update MySQL data through PDO. Views in `app/Views` share the site layout and assets.

The MySQL schema contains customers, portal users, accounts, transactions, transfers, applications, support requests, general contact enquiries, and sign-in records. SQL migrations run when the web container starts, so an existing database volume receives new tables. The `scripts/` directory has commands for account provisioning, password reset, reading locally delivered verification codes, and generating the static page preview.

The Compose stack starts PHP/Apache and MySQL. The web service binds to localhost; MySQL is available only inside the Compose network. Database data and the verification outbox are stored in named Docker volumes.

The site includes public product information documents, a savings interest calculator, an OpenStreetMap area viewer, a general enquiry and tracking flow, a customer dashboard, account statements, transfers, product applications, support requests with attachments, a staff workspace, and JSON endpoints under `/api/v1/` for account activity, request details, application processing, beneficiary lookup, and quick transfers.
