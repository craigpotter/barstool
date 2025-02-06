<?php

declare(strict_types=1);

namespace CraigPotter\Barstool\Tests\Fixtures\Connectors;

use Saloon\Http\Connector;

class RandomConnector extends Connector
{
    public function resolveBaseUrl(): string
    {
        return 'https://craigpotter-not-real.dev';
    }
}
