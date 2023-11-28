<?php

namespace CraigPotter\Barstool\Database\Factories;

use CraigPotter\Barstool\Models\Barstool;
use Saloon\Http\Connectors\NullConnector;
use Illuminate\Database\Eloquent\Factories\Factory;
use CraigPotter\Barstool\Tests\Fixtures\Requests\SoloUserRequest;

class BarstoolFactory extends Factory
{
    protected $model = Barstool::class;

    public function definition()
    {
        return [
            'uuid' => $this->faker->uuid,
            'connector_class' => NullConnector::class,
            'request_class' => SoloUserRequest::class,
            'method' => 'GET',
            'url' => 'https://tests.saloon.dev/api/user',
            'request_headers' => [],
            'request_body' => null,
            'response_headers' => [],
            'response_body' => null,
            'response_status' => 200,
            'successful' => true,
            'duration' => 0,
            'fatal_error' => '',
        ];
    }
}
