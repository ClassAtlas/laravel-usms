<?php

return [
    'api_url' => env('UBILL_API_URL', 'https://api.ubill.dev'),
    'api_key' => env('UBILL_SMS_API_KEY', ''),
    'is_sending_disabled' => env('UBILL_SENDING_DISABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Callback Validation Key
    |--------------------------------------------------------------------------
    | uBill sends this key with every callback (delivery updates, brand
    | activation). It is set in the uBill API application settings. While it
    | is empty every callback is rejected.
    |
    */
    'callback_key' => env('UBILL_CALLBACK_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Provider Timezone
    |--------------------------------------------------------------------------
    | uBill exchanges wall-clock times without an offset: the scheduled
    | `sendTime` of a message and the `date` of a callback. This is the
    | timezone they are written in and read back as.
    |
    */
    'timezone' => env('UBILL_TIMEZONE', 'Asia/Tbilisi'),

    'timeout' => (int) (env('UBILL_TIMEOUT') ?: 30),
    'connect_timeout' => (int) (env('UBILL_CONNECT_TIMEOUT') ?: 10),
    'logging_enabled' => (bool) env('UBILL_LOG_REQUESTS', false),
    'log_channel' => env('UBILL_LOG_CHANNEL'),
];
