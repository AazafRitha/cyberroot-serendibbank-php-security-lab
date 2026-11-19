# SerendibBank Web Application VAPT Report

**Report status:** Draft — assessment not started

**Classification:** Confidential — share only with the assessment owner and approved reviewers

**Prepared for:** CyberRoot / SerendibBank assessment owner

**Prepared by:** [Tester name or team]

**Report version and date:** [Version] / [DD Month YYYY]

This template records the results of an authorized, time-bound assessment of the SerendibBank application. Replace every bracketed field with verified information. Do not describe a test as passed or a finding as confirmed until its evidence has been reviewed.

## 1. Document control

| Version | Date | Author | Change and review status |
| --- | --- | --- | --- |
| 0.1 | [Date] | [Name] | Working draft; findings pending validation |

**Assessment reference:** [Approved scope version and invitation ID]

**Testing window:** [Start and end date, time, and time zone]

**Report recipient:** [Name, role, and private delivery channel]

## 2. Executive summary

**Objective.** [In two or three sentences, state what was assessed and why. Name the exact approved customer URL and whether the management workspace was separately approved.]

**Overall result.** [Summarize only confirmed findings and the most important business impact. If testing is in progress, write “Assessment in progress; no conclusion yet.”]

**Priority actions.** [List the first two or three remediation actions in business language after the findings are confirmed.]

| Critical | High | Medium | Low | Informational | Total confirmed |
| --- | --- | --- | --- | --- | --- |
| [Count] | [Count] | [Count] | [Count] | [Count] | [Count] |

Counts represent unique confirmed root causes. Do not count repeated URLs for the same defect as separate findings.

## 3. Scope and conditions

**Scope authority:** [Link or reference to the completed `docs/assessment-scope.md` engagement record and approval date.]

| Asset or service | Exact approved address or path | Included dates and access |
| --- | --- | --- |
| Customer website and same-origin API | [Full https URL, including current temporary hostname] | [Window and assigned accounts] |
| Management workspace | [Exact URL if separately approved; otherwise “Not in scope”] | [Separate approval reference or “Not in scope”] |

**Exclusions.** Cloudflare infrastructure, other temporary tunnel hostnames, GitHub, email and map providers, the host laptop, VMware, SSH, Docker, MySQL, other VMs and real user data are outside this application assessment unless the signed scope is amended.

**Limits and deviations.** [Record unavailable accounts, failed features, tunnel changes, rate limits, tests deferred for safety, and any written scope changes. State “None recorded” only after checking.]

## 4. Method and coverage

The assessment uses manual browser exploration, targeted HTTP request comparison, role-based access checks and controlled validation against assigned accounts. Record the tools and versions actually used. This report is a point-in-time account of tested behavior; untested paths should be marked as such.

**Tools used:** [For example, browser and version; Burp Suite edition and version; other approved tools]

**Assigned test identities:** [Account aliases and roles only; do not include passwords, one-time codes or full session tokens]

| Test area | Status | Notes or finding IDs |
| --- | --- | --- |
| Public pages and input handling | Not started | [Notes] |
| Registration, login, OTP and sessions | Not started | [Notes] |
| Customer account and transaction authorization | Not started | [Notes] |
| Documents, support and chatbot | Not started | [Notes] |
| Same-origin API | Not started | [Notes] |
| Management workspace, if separately approved | Not in scope | [Approval or exclusion] |

Use **Not started**, **In progress**, **Tested with no finding**, **Finding recorded**, or **Blocked**. “Tested with no finding” means only that the listed checks did not reveal an issue in the test window.

## 5. Severity and findings register

Assign a severity based on exploit conditions, affected data or actions, and realistic impact in this environment. If you calculate CVSS v4.0, record both the score and full vector; leave both as “Not scored” until calculated. Explain any difference between the CVSS rating and the report priority.

| ID | Finding title | Severity | Affected area | Status |
| --- | --- | --- | --- | --- |
| — | No validated findings recorded yet | — | — | Not started |

Use IDs **SB-001**, **SB-002**, and so on. One distinct root cause should normally have one record, with all affected paths listed inside it. Status values are **Draft**, **Confirmed**, **Accepted risk**, **Fixed pending retest**, and **Verified fixed**.

## 6. Detailed finding record to duplicate

Duplicate this entire section for each confirmed or pending finding. Keep the register above in sync with the detailed records.

| Field | Entry |
| --- | --- |
| Finding ID and title | [SB-001 — concise behavior and affected boundary] |
| Severity and rationale | [Critical / High / Medium / Low / Informational; explain why] |
| CVSS v4.0 | [Score and complete vector, or Not scored] |
| Classification | [CWE ID; OWASP WSTG test ID if applicable] |
| Affected URL and method | [Exact approved hostname, path, HTTP method, parameter] |
| Tested on | [Date, time zone, application build or commit if known] |
| Test identities | [Assigned account aliases and roles only] |
| Status and owner | [Draft / Confirmed / Fixed pending retest / Verified fixed; assignee] |

### 6.1 Summary and expected behavior

**Observed behavior:** [What the application did. Describe a verified result, not a possible outcome.]

**Expected behavior:** [What control or access boundary should have applied.]

**Impact:** [Which fictional record or action was affected, what a malicious user could achieve, and any important preconditions.]

### 6.2 Reproduction steps

1. [Start state and assigned account or role.]
2. [Exact page or endpoint and request change.]
3. [Observed response or state change.]
4. [Control comparison, such as a second assigned account or a corrected request.]

**Minimal request and response:** [Paste only the relevant redacted HTTP lines or attach a file referenced below. Remove cookies, tokens, passwords, one-time codes, personal details and unrelated records.]

### 6.3 Evidence and screenshots

| Evidence ID | File or figure | What it proves | Capture time |
| --- | --- | --- | --- |
| E-001 | [Redacted screenshot or request file] | [One sentence describing visible result] | [Date and time zone] |
| E-002 | [Redacted control comparison] | [One sentence describing expected behavior] | [Date and time zone] |

**Screenshot 1 caption:** [Page, account alias, action and result visible in the image. Insert the redacted screenshot below this caption in the Word report.]

**Screenshot 2 caption:** [Control request or second account comparison, if needed. Insert the redacted screenshot below this caption.]

### 6.4 Remediation and retest

**Likely cause:** [Explain the relevant missing check or unsafe behavior, if supported by evidence.]

**Recommended remediation:** [Specific server-side control and where it should be enforced.]

**Cleanup:** [Test records or state changes to undo; write “None” only when verified.]

**Retest result:** [Not tested / Still reproducible / Verified fixed, with date, build and evidence ID.]

## 7. Assessment conclusion

**Conclusion:** [Summarize confirmed findings, untested areas, and any remaining risk after validation. Do not claim the application is secure because a test found no issue.]

**Immediate follow-up:** [Owner, action, and target date for each priority item.]

**Retest plan:** [Exact approved URL, required accounts, proposed window and findings to verify.]

## Appendix A Evidence handling

Keep original capture files in the private evidence folder. Use file names such as `SB-001_E-001_2026-11-19_redacted.png`. In the report, include only the cropped and redacted proof required to reproduce the issue. Preserve the original privately with the capture time and a SHA-256 hash when chain of custody matters. Do not commit raw requests, session tokens, passwords, one-time codes or unredacted data to a public repository.

## Appendix B References

- [Approved SerendibBank assessment scope](../assessment-scope.md)
- [OWASP Web Security Testing Guide reporting structure](https://wstg.owasp.org/latest/5-Reporting/01-Reporting_Structure/)
- [FIRST CVSS v4.0 specification](https://www.first.org/cvss/v4.0/specification-document)
