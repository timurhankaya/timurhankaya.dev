<?php

return [
    'source_root' => env('EDITORIAL_SOURCE_ROOT', '/Users/timurhankaya/Projects/monafy-v2'),
    // Explicit evidence-only repositories; collection remains limited to source_root.
    'evidence_repositories' => [
        'adem' => env('EDITORIAL_ADEM_REPOSITORY', '/Users/timurhankaya/Projects/ADEM'),
    ],
    'ssh_host' => env('EDITORIAL_SSH_HOST'),
    'remote_path' => env('EDITORIAL_REMOTE_PATH'),
    'remote_php' => env('EDITORIAL_REMOTE_PHP', 'php'),
    // Enable only after the owner explicitly approves automatic publication.
    'publish_enabled' => env('EDITORIAL_PUBLISH_ENABLED', false),
    'weekly_limit' => 4,
    // Owner-approved fresh quota start; retain the complete publication ledger.
    'quota_reset_at' => '2026-10-23T14:18:07+00:00',
];
