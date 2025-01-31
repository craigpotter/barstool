<?php

declare(strict_types=1);

return [

    /*
     * If disabled, no requests will be recorded and the UI will not be accessible.
     */
    'enabled' => env('BARSTOOL_ENABLED', true),

    /*
     * The database connection where recordings will be stored.
     */
    'connection' => env('BARSTOOL_DB_CONNECTION', env('DB_CONNECTION', 'mysql')),

    /*
     * The number of days to keep recordings for.
     */
    'keep_for_days' => 30,

    /*
     * The maximum size of the response body that will be stored in kilobytes.
     */
    'max_response_size' => 100,

    /*
     * Indicates if successful responses should be kept.
     * If false, only failed responses will be kept however the request will still be recorded.
     */
    'keep_successful_responses' => true,

    /*
     * Any connectors or requests that should be ignored from recording.
     */
    'ignore' => [
        'connectors' => [
            // SomeConnector::class,
        ],
        'requests' => [
            // SomeRequest::class,
        ],
    ],

    /*
     * Any response body that should be excluded from the recording.
     * This is useful for sensitive data that should not be stored.
     * You may exclude entire connectors or requests by class name.
     */
    'excluded_response_body' => [
        // '*', // All bodies
        // SensitiveConnector::class,
        // SensitiveRequest::class,
    ],

    /*
     * Any headers that should be excluded from the recording.
     * This is useful for sensitive data that should not be stored.
     * Will replace the header value with `REDACTED`.
     * If all headers are ignored or an entire connector/request is ignored, only the X-Barstool-UUID will be logged.
     */
    'excluded_request_headers' => [
        // '*', // All headers
        // 'token' // Exclude `token` header on all requests
        // SensitiveRequest::class // Exclude ALL headers for this request
        // SensitiveConnector::class // Exclude `token` header for this request
    ],
];
