<?php

return [
    'api_url' => env('UBILL_API_URL', 'https://api.ubill.dev'),
    'api_key' => env('UBILL_SMS_API_KEY', ''),
    'is_sending_disabled' => env('UBILL_SENDING_DISABLED', false),
];
