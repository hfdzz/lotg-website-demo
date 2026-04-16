<?php

return [
    'random_token_length' => (int) env('LOTG_RANDOM_TOKEN_LENGTH', 4),
    'random_token_max_attempts' => 3,
    'expected_law_count' => (int) env('LOTG_EXPECTED_LAW_COUNT', 17),
    'export_default_dir' => (string) env('LOTG_EXPORT_DEFAULT_DIR', 'storage/app/lotg-exports'),
    'required_document_slugs' => array_values(array_filter(array_map(
        static fn (string $slug) => trim($slug),
        explode(',', (string) env('LOTG_REQUIRED_DOCUMENT_SLUGS', ''))
    ))),
];
