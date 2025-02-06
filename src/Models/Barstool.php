<?php

declare(strict_types=1);

namespace CraigPotter\Barstool\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use CraigPotter\Barstool\Database\Factories\BarstoolFactory;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;

/**
 * @property string $uuid
 * @property CarbonInterface $created_at
 */
class Barstool extends Model
{
    /** @use HasFactory<BarstoolFactory> */
    use HasFactory;

    use MassPrunable;

    public const string|null UPDATED_AT = null;

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
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setConnection(config('barstool.connection'));
    }

    /**
     * Get the prunable model query.
     *
     * @return EloquentBuilder<static>
     */
    public function prunable(): EloquentBuilder
    {
        return static::query()
            ->where(
                'created_at',
                '<=',
                now()->subDays(config('barstool.keep_for_days', 0))
            );
    }
}
