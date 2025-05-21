<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the connections below you wish to use
    | as your default connection for IdoSell API work.
    |
    */
    'default' => env('IDOSELL_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | IdoSell API Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the connections setup for your application.
    |
    */
    'connections' => [
        'default' => [
            'api_key' => env('API_IDOSELL_KEY'),
            'domain_url' => env('API_SHOP_DOMAIN'),
        ],
        
        'second_shop' => [
            'api_key' => env('API_IDOSELL_KEY_SECOND'),
            'domain_url' => env('API_SHOP_DOMAIN_SECOND'),
        ],

        // You can add more connections here
    ],
];