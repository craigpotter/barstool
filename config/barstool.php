<?php

return [

    /*
     * If disabled, no requests will be recorded and the UI will not be accessible.
     */
    'enabled' => env('BARSTOOL_ENABLED', true),

    /*
     * The route prefix used to access the Barstool UI.
     */
    'path' => 'barstool',

    /*
     * The middleware used on the Barstool UI route.
     */
    'middleware' => [
        'web',
        'auth',
    ],

    /*
     * The database connection where recordings will be stored.
     */
    'connection' => env('DB_CONNECTION', 'mysql'),

    /*
     * The number of days to keep recordings for.
     */
    'keep_for_days' => 30,

    /*
     * Indicates if successful responses should be kept.
     * If false, only failed responses will be kept however the request will still be recorded.
     */
    'keep_successful_responses' => true,

    /*
     * Any connectors or requests that should be ignored from recording.
     */
    'ignore' => [
        // SomeConnector::class,
        // GetToken::class,
    ],

    /*
     * Any connectors or requests that should only be recorded if they fail.
     */
    'only_failed' => [
        // SomeConnector::class,
        // GetToken::class,
    ],

    /*
     * Any connectors or requests that exclude the response body of the recording.
     */
    'exclude_response_body' => [
        // SensitiveConnector::class,
        // SensitiveRequest::class,
    ],

    /*
     * Any headers that should be excluded from the recording.
     */
    'excluded_request_headers' => [
        // '*', // All headers
        // 'token' // Exclude `token` header on all requests
        // SomeRequest::class // Exclude ALL headers for this request
    ],
];
