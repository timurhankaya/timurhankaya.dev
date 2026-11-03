# Admin draft preview on the live story URL

The owner explicitly requested viewing drafts on the live site after admin login with a query parameter. The exact approved SaaS revision is applied first; subsequent revisions remain private pending approval.

Use `?draft=1` on article and case-study URLs in either language. Reuse the existing admin-only preview renderer and its noindex/private-no-store response. Guests go to admin login, ordinary users receive 403. A canonical redirect keeps the draft parameter. Normal requests continue to use published records only.

Existing unpublished database posts can be previewed. An existing published post can also have a separate private staged story JSON; rendering overlays only editable text fields on an in-memory copy. It does not save or publish the post. A CLI staging command validates one existing story and stores it privately with a content baseline hash. A stale baseline returns 409 rather than silently showing an outdated draft. Missing staged data previews the current database version.

Alternatives considered: changing live content before approval defeats the review process; adding a revision table/migration is unnecessary for one-at-a-time review. Private staged JSON fits the current editorial workflow and preserves the public row.

Scope: focused PHP code installation on production, no unrelated workspace changes, no migrations, no Monafy source changes, no permission/quota changes. Tests and npm build run only at finalization per owner instruction.
