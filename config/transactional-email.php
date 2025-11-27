<?php

return [
    // Base API URL for the transactional email service (no trailing slash)
    'base_url' => env('TRANSACTIONAL_EMAIL_BASE_URL', 'http://127.0.0.1:8000/api'),

    // Required App ID (UUID) used for template sends when not explicitly provided.
    // Set this in your .env as APP_ID
    'app_id' => env('APP_ID'),

    // API endpoints (relative to base_url). You can override via env if needed.
    'endpoints' => [
        'login' => env('TRANSACTIONAL_EMAIL_LOGIN_ENDPOINT', '/login'),
        'template' => env('TRANSACTIONAL_EMAIL_TEMPLATE_ENDPOINT', '/gettransactionalApi'),
        'direct' => env('TRANSACTIONAL_EMAIL_DIRECT_ENDPOINT', '/makeTransactionalApi'),
    ],

    // HTTP behavior
    'http' => [
        'timeout' => (int) env('TRANSACTIONAL_EMAIL_TIMEOUT', 10),
        // For local dev you may disable SSL verification. Enable in production.
        'verify_ssl' => (bool) env('TRANSACTIONAL_EMAIL_VERIFY_SSL', false),
    ],

    // Optional credentials for automatic login
    'credentials' => [
        'email' => env('TRANSACTIONAL_EMAIL_LOGIN_EMAIL'),
        'password' => env('TRANSACTIONAL_EMAIL_LOGIN_PASSWORD'),
    ],
];

