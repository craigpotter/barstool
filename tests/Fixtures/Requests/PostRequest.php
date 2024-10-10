<?php

namespace CraigPotter\Barstool\Tests\Fixtures\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Contracts\Body\HasBody;
use Saloon\Traits\Body\HasStreamBody;

class PostRequest extends Request implements HasBody
{
    use HasStreamBody;
    /**
     * Define the HTTP method.
     */
    protected Method $method = Method::POST;

    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'text/plain',
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function resolveEndpoint(): string
    {
        return 'user';
    }
}
