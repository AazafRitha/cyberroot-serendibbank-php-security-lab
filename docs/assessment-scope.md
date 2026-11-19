# SerendibBank Security Assessment Scope

**Version:** 0.1 draft

**Owner:** CyberRoot

**Prepared:** 19 November 2026

**Status:** Complete the engagement record below and obtain owner approval before inviting external testers. This document does not authorize testing of third-party systems.

## 1. Engagement record

| Field | Value to record before testing |
| --- | --- |
| Assessment owner and approver | ____________________ |
| Exact customer URL including scheme | ____________________ |
| Management URL, if separately approved | ____________________ / not included |
| Start and end date with time zone | ____________________ |
| Private reporting email or portal | ____________________ |
| Emergency stop contact | ____________________ |
| Tester names or invitation identifiers | ____________________ |
| Assigned customer and staff account IDs | ____________________ |
| Recovery snapshot identifier and owner | ____________________ |
| Approval date and reference | ____________________ |

The owner must issue the completed record to each tester. A Cloudflare Quick Tunnel address may change after a restart. Only the exact hostname written in the current invitation is covered; `*.trycloudflare.com` is never a wildcard target. A new URL or testing window requires an updated record. The VM's private address, localhost address, and public tunnel address are different access paths; none implies authorization for the others.

## 2. In scope

The assessment is limited to the SerendibBank application on the exact customer URL in the engagement record. The following application areas are included when reachable on that origin:

| Area | Included functions | Conditions |
| --- | --- | --- |
| Public website | Navigation, product pages, search, calculators, branches, contact and chat | Test application behavior on the assigned hostname only. |
| Customer identity | Registration, sign-in, one-time code verification, sessions and profile | Use designated fictional identities and accounts. |
| Customer banking | Dashboard, accounts, transfers, beneficiaries, statements, documents and applications | Use only assigned accounts and owner-approved test amounts. |
| Customer support | Contact requests, support cases, chatbot and transcript | Keep submissions clearly identifiable as test records. |
| Same-origin API | Endpoints under `/api/v1/` on the assigned customer hostname | Apply the same account and traffic limits as the website. |

Authentication, authorization, session handling, input handling, business logic, file handling and information exposure may be tested within these areas. Discovery of an unlisted application path on the same hostname does not expand scope to a separate service or infrastructure interface.

**Management workspace:** `manage.localhost` and any future management hostname are excluded from the external invitation by default. An internal assessment may include the management application only when its exact hostname, access path, tester identities, staff accounts and dates are recorded separately above. Finding a way to reach that workspace through the customer site should be reported with minimal proof; do not browse or alter unrelated records.

## 3. Out of scope

- Any hostname, IP address, port, service or tenant absent from the completed engagement record, including other `trycloudflare.com` sites.
- Cloudflare Access, Cloudflare Tunnel and other Cloudflare infrastructure; the GitHub repository and GitHub accounts; company SMTP or email-provider infrastructure; OpenStreetMap and other third-party integrations.
- The Windows laptop, VMware host or hypervisor, other VMs, internal network, SSH, Docker daemon, MySQL service and operating system. These are excluded even if reachable from the testing machine.
- Real customer, staff or third-party identities, credentials, mailboxes, payment methods or data. Testing must use supplied fictional records.
- Availability, capacity and physical security tests, including denial of service, stress tests, resource exhaustion and attacks on network equipment.
- Social engineering, phishing, telephone calls, physical entry, malware, persistence, cryptocurrency mining and attempts to compromise tester or operator devices.

## 4. Rules of engagement

1. Test only during the approved window and only from the assigned access path. If the tunnel URL changes, pause until the owner issues a new exact URL.
2. Manual testing and targeted Burp Repeater checks are permitted. Automated request generation requires owner approval. When approved, stay at or below **2 requests per second per tester**, with no parallel high-volume scans. Stop if latency or errors rise.
3. For horizontal access-control tests, use two accounts assigned to the same tester. For role-boundary tests, use only staff accounts expressly assigned in the internal addendum. Do not enumerate or authenticate other participants' accounts.
4. Use test-only transfers between assigned accounts, with a maximum of **LKR 100 per transaction** unless the owner approves another value. Do not initiate external payments, repeated transfers or bulk changes.
5. Keep proofs small and reversible. Do not dump tables, collect credentials or one-time codes, download entire document sets, change another user's password, or persist access. A single redacted record or minimal response difference is sufficient proof.
6. Do not submit payloads that execute commands on the host, destroy data, send mail to third parties, create long-running jobs or deliberately interrupt service. Ask the owner for a separately controlled test window if such validation is essential.
7. If unexpected personal information, a path to host or third-party infrastructure, or a service disruption appears, stop that line of testing and use the emergency contact. Do not retain or share the data.
8. Keep findings private until the owner has reviewed them and agreed on disclosure. No reward, payment or legal safe-harbor promise is made by this draft; those terms need separate approval before a public program opens.

The operator should prepare a VM snapshot, confirm fictional data, monitor the service, and keep a restore and tunnel shutdown procedure available throughout the window.

## 5. Reporting and evidence

Send each finding through the private channel in the engagement record. A useful report contains:

- Title, affected exact URL and test time with time zone.
- Category (for example OWASP WSTG area or CWE), observed behavior and expected behavior.
- Preconditions and the assigned test-account roles used.
- Numbered reproduction steps and a minimal request/response pair or screenshots. Redact cookies, tokens, passwords, one-time codes, personal details and unrelated records.
- Practical impact, affected account boundary and any state changed during testing.
- Suggested fix, if known, and cleanup or reset needed.

The owner should acknowledge receipt, preserve the evidence privately, validate with the assigned test accounts, record severity and remediation status, and communicate a disclosure decision. Response times and reward terms are **not yet set**; add them to the invitation only after approval.

## 6. Assessment closeout

At the end of the window, testers stop active requests and submit outstanding reports. The owner checks transactions and support records created during testing, revokes test sessions, rotates any exposed test credentials, restores the agreed snapshot if needed, and closes or changes the tunnel URL. Retesting requires a new approved window or explicit extension.

## 7. Program publication checklist

- [ ] Confirm the exact current customer hostname and the testing dates.
- [ ] Confirm the private reporting channel and emergency contact.
- [ ] Assign isolated fictional accounts to each tester.
- [ ] Decide whether management access is included in a separate internal addendum.
- [ ] Check the VM snapshot, monitoring and recovery steps.
- [ ] Approve the rules and give testers the completed engagement record.
- [ ] Review the public `/security-research` page against the final invitation.

## 8. Internal management addendum

Complete this section only if the assessment owner authorizes testing of the management workspace. It does not expand the public customer-site invitation.

| Field | Internal approval entry |
| --- | --- |
| Exact management URL and access path | ____________________ |
| Approved staff roles and account IDs | ____________________ |
| Approved management actions | ____________________ |
| Restricted records or operations | ____________________ |
| Separate start and end time | ____________________ |
| Internal approver and date | ____________________ |

If approved, testers may assess authentication, role separation, customer lookup, account and transaction administration, case handling and session management using only assigned fictional records. A test that changes account status, balance, transaction review state or staff roles must be prearranged with the owner and documented for rollback. Access through a discovered bypass is evidence to report, not permission to explore unrelated management data.

## 9. Approval and change control

The assessment owner signs the completed engagement record and gives each tester the same version. Record any change to hostname, dates, accounts, rate limits or management access before it takes effect. A chat message or a public URL alone is not an amendment to scope.

**Owner approval:** ____________________  **Date:** ____________________

**Technical reviewer:** ____________________  **Date:** ____________________

## References

- [OWASP Web Security Testing Guide](https://wstg.owasp.org/) for application testing categories and methodology.
- [CISA vulnerability disclosure policy guidance](https://www.cisa.gov/sites/default/files/bod-20-01.pdf) for clear public statements of scope, permitted testing and reporting.

This is an assessment operating document, not legal advice or an authorization from any third-party provider.
