<?php

namespace CraigPotter\Barstool\Models;

use Illuminate\Database\Eloquent\Model;

class Barstool extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'connector_class',
        'request_class',
        'method',
        'url',
        'request_headers',
        'request_body',
        'response_headers',
        'response_body',
        'response_status',
        'successful',
        'duration',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'successful' => 'boolean',
    ];

}
