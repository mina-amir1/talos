<?php

return [
    'name' => 'Talos',
    'version' => '1.0.0',

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    */
    'admin_prefix' => env('TALOS_ADMIN_PREFIX', 'talos'),
    'admin_title'  => env('TALOS_ADMIN_TITLE', 'Talos CMS'),

    /*
    |--------------------------------------------------------------------------
    | Schema Storage
    |--------------------------------------------------------------------------
    | Where content-type and component JSON schemas are stored.
    */
    'schema_path' => env('TALOS_SCHEMA_PATH', base_path('talos')),

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */
    'api_prefix'   => env('TALOS_API_PREFIX', 'api'),
    'api_version'  => env('TALOS_API_VERSION', 'v1'),

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    */
    'media_disk'      => env('TALOS_MEDIA_DISK', 'public'),
    'media_directory' => env('TALOS_MEDIA_DIR', 'talos/media'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'default_page_size' => env('TALOS_PAGE_SIZE', 25),

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    | default_locale : used when no ?locale= param is given
    | locales        : comma-separated list of enabled locale codes
    */
    'default_locale' => env('TALOS_DEFAULT_LOCALE', 'en'),
    'locales'        => array_filter(array_map('trim', explode(',', env('TALOS_LOCALES', 'en')))),
];
