<?php

namespace CraigPotter\Barstool\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * @property string $uuid
 */
class Barstool extends Model
{
    use HasFactory;
    use MassPrunable;

    const UPDATED_AT = null;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setConnection(config('barstool.connection'));
    }

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
        'fatal_error',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'response_headers' => 'array',
        'successful' => 'boolean',
    ];

    /**
     * Get the prunable model query.
     */
    public function prunable()
    {
        return static::where('created_at', '<=', now()->subDays(config('barstool.keep_for_days')));
    }
}
