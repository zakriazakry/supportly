<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */
    'evolution' => [
        'api_key' => env('EVOLUTION_API_KEY'),
        'base_url' => env('EVOLUTION_BASE_URL'),
    ],

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://31.97.154.208:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2'),
        'timeout' => env('OLLAMA_TIMEOUT', 120),
    ],
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    // config/services.php
    'facebook' => [
        'client_id' => env('FACEBOOK_CLIENT_ID'),
        'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
        'redirect' => env('FACEBOOK_REDIRECT_URI'),
        // تحديد الصلاحيات المطلوبة
        'scopes' => [
            'pages_show_list',      // لعرض قائمة صفحات المستخدم
            'pages_manage_posts',   // للردود العامة على المنشورات
            'pages_messaging',      // للردود الخاصة (Private Replies)
            'public_profile',
        ],
    ],
];
