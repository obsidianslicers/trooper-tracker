<?php

return [

    'ssr' => [
        'enabled' => (bool) env('INERTIA_SSR_ENABLED', false),
        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),
        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', false),
    ],
    'ensure_pages_exist' => false,
    'page_paths' => [
        resource_path('svelte/pages'),
    ],
    'page_extensions' => [
        'svelte',
        'ts',
    ],
    'use_script_element_for_initial_page' => true,
];
