<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Versi aplikasi mobile (gatekeeper splash screen)
    |--------------------------------------------------------------------------
    |
    | MIN_APP_VERSION: di bawah ini → FORCE_UPDATE
    | LATEST_APP_VERSION: di bawah ini (tapi >= min) → OPTIONAL_UPDATE
    |
    */

    'latest' => env('LATEST_APP_VERSION', '1.0.0'),

    'min' => env('MIN_APP_VERSION', '1.0.0'),

    'update_urls' => [
        'android' => env('APP_UPDATE_URL_ANDROID', 'https://play.google.com/store/apps/details?id=com.andersonfarm.app'),
        'ios' => env('APP_UPDATE_URL_IOS', 'https://apps.apple.com/app/anderson-farm/id000000000'),
    ],

];
