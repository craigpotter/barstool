<?php

declare(strict_types=1);

namespace CraigPotter\Barstool\Tests\Fixtures\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class RequestWithConnector extends Request
{
    /**
     * Define the HTTP method.
     *
     * @var string
     */
    protected Method $method = Method::GET;

    protected function defaultHeaders(): array
    {
        return [
            'testing' => 'headers',
        ];
    }

    /**
     * Define the endpoint for the request.
     */
    public function resolveEndpoint(): string
    {
        return 'https://tests.saloon.dev/api/user';
    }
}
