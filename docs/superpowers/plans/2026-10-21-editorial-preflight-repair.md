# Editorial preflight repair

Scope: correct client-specific HTTP failure reporting and avoid expensive research when publication is unavailable. Leave unrelated workspace edits untouched. The owner's repair request authorizes these focused code changes; recurring editorial runs retain their no-code/no-deployment boundary.

1. Reproduce the original client failure and compare a normal HTTP client. Audit public sitemap URLs with bounded direct GETs.
2. Add `editorial:status --remote`, backed by the existing production publication ledger. Include removed publications and approved batches when computing the next available slot. Add regression coverage for empty capacity, a batch above the limit, expiration and invalid SSH configuration.
3. Update editorial instructions and the existing automation: production status first, immediate stop when unavailable/full/disabled, one candidate, 15-minute budget, phase timestamps and client-aware HTTP verification.
4. At finalization, run the relevant tests, full suite and frontend build per owner instructions. Install only the new status command in production, then exercise the real read-only status and recheck public URLs. No content publication or unrelated deployment.

Review focus: quota above the cap, removed entries, exact seven-day expiry, unknown remote status, no production writes.
