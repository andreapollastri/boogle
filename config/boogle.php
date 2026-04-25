<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Panel display (ingested data)
    |--------------------------------------------------------------------------
    |
    | The client may send full HTTP context (headers, session). Set to false
    | to avoid showing those sections in the exception detail view (privacy).
    |
    */
    'ui' => [
        'show_http_headers' => (bool) env('BOOGLE_UI_SHOW_HTTP_HEADERS', true),
        'show_http_session' => (bool) env('BOOGLE_UI_SHOW_HTTP_SESSION', true),
    ],
];
