# Page and security scenario map

The public site presents SerendibBank as a fictional retail bank. This repository explains its educational purpose. Copy and original visuals follow common Sri Lankan banking patterns without reusing other banks' assets or claiming real partner offers.

| Banking area | Live route | Functional workflow | Main-site lab weakness |
| --- | --- | --- | --- |
| Home and products | `/`, `/personal`, `/cards`, `/offers`, `/digital` | Product navigation and discovery | Entry points to the banking flows |
| Online banking | `/login` | Customer or staff authentication | [Account enumeration and no throttling](../validation/main-site.md#weak-login) |
| My banking | `/dashboard`, `/statement` | Accounts, activity, statements | [Statement access control](../validation/main-site.md#account-statement-access) |
| Transfers | `/transfer`, `/transfer/repeat` | Own-account funds movement | [State-changing GET](../validation/main-site.md#repeat-transfer-csrf) |
| Applications | `/apply/account`, `/apply/card` | Product-specific forms and status tracking | Ownership checks in [core validation](../validation/core-portal.md) |
| Branches and ATMs | `/locations`, `/locations/search` | Location finder and city search | [SQL injection](../validation/main-site.md#branch-search-sql-injection) |
| Help and support | `/support`, `/chat`, `/chat/transcript`, `/support/request` | Guided assistant and support requests | [Chat-to-staff stored XSS](../validation/main-site.md#assistant-handoff-stored-xss) |
| Service workspace | `/staff` | Staff review of applications and requests | Renders escalated chat text in lab mode |

The public offers and quick location filters operate on fixed browser content. The server branch search runs a SQL query over fictional branch rows. Applications only create review requests; submitting one does not issue a card or open an account. There is no real payment rail or real customer data. A specific PHP CVE was not supplied, so no PHP runtime advisory exercise is claimed.
