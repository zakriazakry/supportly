<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Evolution API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for Evolution API integration.
    | Make sure to set the correct values in your .env file.
    |
    */

    'api_key' => env('EVOLUTION_API_KEY'),
    'base_url' => env('EVOLUTION_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Default Integration Type
    |--------------------------------------------------------------------------
    |
    | The default integration type to use when creating new instances.
    | Options: WHATSAPP-BAILEYS, WHATSAPP-BUSINESS
    |
    */

    'default_integration' => env('EVOLUTION_DEFAULT_INTEGRATION', 'WHATSAPP-BAILEYS'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Default webhook configuration for new instances.
    |
    */

    'webhook' => [
        'enabled' => env('EVOLUTION_WEBHOOK_ENABLED', true),
        'url' => env('EVOLUTION_WEBHOOK_URL', env('APP_URL') . '/api/evolution/webhook'),
        'by_events' => env('EVOLUTION_WEBHOOK_BY_EVENTS', false),
        'base64' => env('EVOLUTION_WEBHOOK_BASE64', true),
        'events' => [
            'APPLICATION_STARTUP',
            'QRCODE_UPDATED',
            'MESSAGES_UPSERT',
            'CONNECTION_UPDATE',
            'SEND_MESSAGE',
            'CHATS_UPSERT',
            'GROUPS_UPSERT',
            'GROUP_UPDATE',
            'GROUP_PARTICIPANTS_UPDATE',
            'CALL',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Instance Settings
    |--------------------------------------------------------------------------
    |
    | Default settings to apply when creating new instances.
    |
    */

    'settings' => [
        'reject_call' => env('EVOLUTION_REJECT_CALL', true),
        'msg_call' => env('EVOLUTION_MSG_CALL', 'عذراً، لا أقبل المكالمات'),
        'groups_ignore' => env('EVOLUTION_GROUPS_IGNORE', false),
        'always_online' => env('EVOLUTION_ALWAYS_ONLINE', true),
        'read_messages' => env('EVOLUTION_READ_MESSAGES', false),
        'read_status' => env('EVOLUTION_READ_STATUS', false),
        'sync_full_history' => env('EVOLUTION_SYNC_FULL_HISTORY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Message Delays
    |--------------------------------------------------------------------------
    |
    | Default delays (in milliseconds) to use when sending messages.
    | This helps prevent being blocked by WhatsApp.
    |
    */

    'delays' => [
        'between_messages' => env('EVOLUTION_DELAY_BETWEEN_MESSAGES', 1200),
        'typing_simulation' => env('EVOLUTION_DELAY_TYPING', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    |
    | Proxy settings for instances (optional).
    |
    */

    'proxy' => [
        'enabled' => env('EVOLUTION_PROXY_ENABLED', false),
        'host' => env('EVOLUTION_PROXY_HOST'),
        'port' => env('EVOLUTION_PROXY_PORT'),
        'protocol' => env('EVOLUTION_PROXY_PROTOCOL', 'http'),
        'username' => env('EVOLUTION_PROXY_USERNAME'),
        'password' => env('EVOLUTION_PROXY_PASSWORD'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Chatwoot Integration
    |--------------------------------------------------------------------------
    |
    | Chatwoot integration settings (optional).
    |
    */

    'chatwoot' => [
        'enabled' => env('EVOLUTION_CHATWOOT_ENABLED', false),
        'account_id' => env('EVOLUTION_CHATWOOT_ACCOUNT_ID'),
        'token' => env('EVOLUTION_CHATWOOT_TOKEN'),
        'url' => env('EVOLUTION_CHATWOOT_URL'),
        'sign_msg' => env('EVOLUTION_CHATWOOT_SIGN_MSG', true),
        'reopen_conversation' => env('EVOLUTION_CHATWOOT_REOPEN_CONVERSATION', true),
        'conversation_pending' => env('EVOLUTION_CHATWOOT_CONVERSATION_PENDING', false),
        'import_contacts' => env('EVOLUTION_CHATWOOT_IMPORT_CONTACTS', true),
        'name_inbox' => env('EVOLUTION_CHATWOOT_NAME_INBOX', 'WhatsApp'),
        'merge_brazil_contacts' => env('EVOLUTION_CHATWOOT_MERGE_BRAZIL_CONTACTS', false),
        'import_messages' => env('EVOLUTION_CHATWOOT_IMPORT_MESSAGES', true),
        'days_limit_import_messages' => env('EVOLUTION_CHATWOOT_DAYS_LIMIT_IMPORT_MESSAGES', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | RabbitMQ Integration
    |--------------------------------------------------------------------------
    |
    | RabbitMQ integration settings (optional).
    |
    */

    'rabbitmq' => [
        'enabled' => env('EVOLUTION_RABBITMQ_ENABLED', false),
        'events' => [
            'MESSAGES_UPSERT',
            'CONNECTION_UPDATE',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SQS Integration
    |--------------------------------------------------------------------------
    |
    | AWS SQS integration settings (optional).
    |
    */

    'sqs' => [
        'enabled' => env('EVOLUTION_SQS_ENABLED', false),
        'events' => [
            'MESSAGES_UPSERT',
            'CONNECTION_UPDATE',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typebot Integration
    |--------------------------------------------------------------------------
    |
    | Typebot integration settings (optional).
    |
    */

    'typebot' => [
        'enabled' => env('EVOLUTION_TYPEBOT_ENABLED', false),
        'url' => env('EVOLUTION_TYPEBOT_URL'),
        'typebot' => env('EVOLUTION_TYPEBOT_ID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable/disable logging for Evolution API requests and webhooks.
    |
    */

    'logging' => [
        'enabled' => env('EVOLUTION_LOGGING_ENABLED', true),
        'channel' => env('EVOLUTION_LOG_CHANNEL', 'daily'),
        'level' => env('EVOLUTION_LOG_LEVEL', 'info'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting settings to prevent abuse.
    |
    */

    'rate_limiting' => [
        'enabled' => env('EVOLUTION_RATE_LIMITING_ENABLED', true),
        'max_requests_per_minute' => env('EVOLUTION_MAX_REQUESTS_PER_MINUTE', 60),
        'max_messages_per_minute' => env('EVOLUTION_MAX_MESSAGES_PER_MINUTE', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Auto-Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Automatic retry settings for failed requests.
    |
    */

    'retry' => [
        'enabled' => env('EVOLUTION_RETRY_ENABLED', true),
        'max_attempts' => env('EVOLUTION_RETRY_MAX_ATTEMPTS', 3),
        'delay_ms' => env('EVOLUTION_RETRY_DELAY_MS', 1000),
    ],

];
