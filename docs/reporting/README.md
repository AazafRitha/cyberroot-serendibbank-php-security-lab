# VAPT reporting workflow

1. Review the approved [assessment scope](../assessment-scope.md). Record its exact URL, time window and assigned accounts in the report before testing.
2. Open [the Word template](SerendibBank-VAPT-Report-Template.docx) and save a working copy under `reports/private/` on your laptop. That directory is ignored by Git. The [Markdown template](vapt-report-template.md) is available for VS Code editing.
3. For each candidate issue, add a row to the findings register and duplicate the detailed finding section. Mark it **Draft** until you have a clear control comparison and have checked that it is a distinct root cause.
4. Save original captures privately. Add cropped, redacted screenshots to the Word report with evidence IDs and captions. Show the URL or endpoint, test-account role, action, and observed result. A second screenshot can show expected behavior with another assigned account.
5. Validate severity and impact, then mark the finding **Confirmed**. Use a CVSS v4.0 score only when you can provide its full vector. Keep the business priority explanation separate from the score.
6. Share the finished report through the private reporting channel named in the engagement record. Do not publish raw evidence or a completed report to the public GitHub repository before the assessment owner approves disclosure.

The template has no claimed findings. Replace every bracketed field and remove unused finding sections before final delivery.
