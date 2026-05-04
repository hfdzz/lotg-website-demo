<?php

return [
    'random_token_length' => (int) env('LOTG_RANDOM_TOKEN_LENGTH', 4),
    'random_token_max_attempts' => 3,
    'expected_law_count' => (int) env('LOTG_EXPECTED_LAW_COUNT', 17),
    'export_default_dir' => (string) env('LOTG_EXPORT_DEFAULT_DIR', 'storage/app/lotg-exports'),
    'media_upload_disks' => array_values(array_filter(array_map(
        static fn (string $disk) => trim($disk),
        explode(',', (string) env('LOTG_MEDIA_UPLOAD_DISKS', 'public,s3'))
    ))),
    'media_default_upload_disk' => (string) env('LOTG_MEDIA_DEFAULT_UPLOAD_DISK', 'public'),
    'video_upload_max_kb' => (int) env('LOTG_VIDEO_UPLOAD_MAX_KB', 51200),
    'public_features' => [
        'documents' => [
            'label' => 'Documents',
            'description' => 'Show supporting LotG documents inside the public hub and allow their public detail pages.',
            'default' => true,
        ],
        'qas' => [
            'label' => 'Q&A',
            'description' => 'Show the public Q&A area for the current active edition.',
            'default' => true,
        ],
        'legacy_updates' => [
            'label' => 'Law changes',
            'description' => 'Show the legacy law-changes / updates page in the public site navigation.',
            'default' => true,
        ],
    ],
    'required_document_slugs' => array_values(array_filter(array_map(
        static fn (string $slug) => trim($slug),
        explode(',', (string) env('LOTG_REQUIRED_DOCUMENT_SLUGS', ''))
    ))),
];
