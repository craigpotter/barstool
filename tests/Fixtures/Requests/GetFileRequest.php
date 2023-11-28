<?php

namespace CraigPotter\Barstool\Tests\Fixtures\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetFileRequest extends Request
{
    /**
     * Define the HTTP method.
     */
    protected Method $method = Method::GET;

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return 'https://http.cat/images/418.jpg';
    }
}
