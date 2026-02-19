<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Provider
    |--------------------------------------------------------------------------
    |
    | The default AI provider for image generation.
    | Supported by Laravel AI SDK: "openai", "gemini", "xai"
    |
    */
    'default_provider' => env('AI_IMAGE_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Generation Timeout (seconds)
    |--------------------------------------------------------------------------
    */
    'timeout' => (int) env('AI_IMAGE_TIMEOUT', 120),

    /*
    |--------------------------------------------------------------------------
    | Daily Generation Limit Per User
    |--------------------------------------------------------------------------
    |
    | Set to 0 for unlimited.
    |
    */
    'daily_limit' => (int) env('AI_IMAGE_DAILY_LIMIT', 20),

    /*
    |--------------------------------------------------------------------------
    | Monthly Generation Limit Per User
    |--------------------------------------------------------------------------
    |
    | Set to 0 for unlimited.
    |
    */
    'monthly_limit' => (int) env('AI_IMAGE_MONTHLY_LIMIT', 200),

    /*
    |--------------------------------------------------------------------------
    | System Prompt
    |--------------------------------------------------------------------------
    |
    | Prepended to every image generation prompt to enforce professional output.
    |
    */
    'system_prompt' => env('AI_IMAGE_SYSTEM_PROMPT',
        'You are creating a professional image for a corporate communication. '
        . 'The output must be polished, brand-appropriate, and suitable for a general audience. '
        . 'Stay strictly within the context provided. Do not add unrelated elements.'
    ),

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    */
    'storage_disk' => env('AI_IMAGE_DISK', 'public'),

    /*
    |--------------------------------------------------------------------------
    | Max Reference Image Size (KB)
    |--------------------------------------------------------------------------
    */
    'max_reference_size_kb' => (int) env('AI_IMAGE_MAX_REF_SIZE', 4096),

];
