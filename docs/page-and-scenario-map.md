# Page and security scenario map

The public site presents SerendibBank as a fictional retail bank. This repository explains its educational purpose. Copy and original visuals follow common Sri Lankan banking patterns without reusing other banks' assets or claiming real partner offers.

| Banking area | Live route | Functional workflow | Isolated exercise |
| --- | --- | --- | --- |
| Home and products | `/`, `/personal`, `/cards`, `/offers`, `/digital` | Product navigation and discovery | None in the core |
| Online banking | `/login` | Customer or staff authentication | [Weak login](../validation/weak-login.md) |
| My banking | `/dashboard` | Customer-scoped accounts and activity | [Broken access control](../validation/broken-access-control.md) |
| Transfers | `/transfer` | Own-account funds movement | [CSRF](../validation/csrf-transfer.md) |
| Applications | `/apply/account`, `/apply/card` | Product-specific forms and status tracking | Ownership checks in [core validation](../validation/core-portal.md) |
| Branches and ATMs | `/locations` | Public location finder | [SQL injection](../validation/sql-injection.md) |
| Help and support | `/support`, `/support/request` | FAQ and authenticated message form | [Stored XSS](../validation/stored-xss.md) |
| Service workspace | `/staff` | Staff review of applications and requests | Role checks in [core validation](../validation/core-portal.md) |

The public offers and location filters operate on fixed browser content; they are not database-backed in the core. The SQL injection scenario has its own branch table. Applications only create review requests; submitting one does not issue a card or open an account. There is no real payment rail or real customer data. A specific PHP CVE was not supplied, so no PHP runtime advisory exercise is claimed.
