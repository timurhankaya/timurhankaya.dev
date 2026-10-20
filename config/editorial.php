<?php

return [
    'source_root' => env('EDITORIAL_SOURCE_ROOT', '/Users/timurhankaya/Projects/monafy-v2'),
    'ssh_host' => env('EDITORIAL_SSH_HOST'),
    'remote_path' => env('EDITORIAL_REMOTE_PATH'),
    'remote_php' => env('EDITORIAL_REMOTE_PHP', 'php'),
    // Enable only after the owner explicitly approves automatic publication.
    'publish_enabled' => env('EDITORIAL_PUBLISH_ENABLED', false),
    'weekly_limit' => 2,
];
