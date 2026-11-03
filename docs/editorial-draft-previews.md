# Admin review on the live story page

Append `?draft=1` to either language's canonical article/case-study URL. Sign in at `/admin` first, or follow the preview's login redirect. Only `is_admin` users can preview. The preview carries noindex/nofollow and private/no-store headers. The language switch keeps draft mode.

Database drafts render without publishing them. Revisions of an existing published story are staged separately:

```
php artisan stories:stage storage/app/private/editorial/revisions/REVISION/stories.json --revision=REVISION-ID --remote
```

The `--remote` option transfers only the story payload through the configured editorial SSH connection. Omit it to stage in the local app. Never upload private evidence, source snapshots or credentials to a public directory. The command validates one story, requires an existing matching slug/type/project and stores the pending payload at `storage/app/private/editorial/previews/POST_ID.json`. It does not alter the database or publication ledger. Scheduled runs can stage a pending draft but cannot apply it to live content.

The preview overlays editable text in memory; URLs, dates, type and project identity stay fixed. If the live baseline changes after staging, preview returns 409 and must be restaged from the refreshed baseline. If no staged file exists, preview shows the current database record. Soft-deleted posts remain unavailable through the public query route.

After exact owner approval, use the existing guarded content sync and verify both live pages. Remove only that story's staged preview file after success. Keep review history and receipts. Normal public URLs, listings, feeds and sitemaps use only the published database version.
