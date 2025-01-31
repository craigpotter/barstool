<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Illuminate\Support\Facades\Artisan;
use CraigPotter\Barstool\Models\Barstool;
use Saloon\Http\Connectors\NullConnector;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseEmpty;

use Saloon\Exceptions\Request\FatalRequestException;
use CraigPotter\Barstool\Tests\Fixtures\Requests\PostRequest;
use CraigPotter\Barstool\Tests\Fixtures\Requests\GetFileRequest;
use CraigPotter\Barstool\Tests\Fixtures\Requests\SoloUserRequest;
use CraigPotter\Barstool\Tests\Fixtures\Connectors\RandomConnector;
use CraigPotter\Barstool\Tests\Fixtures\Requests\RequestWithConnector;

it('can be enabled', function () {
    config()->set('barstool.enabled', true);

    MockClient::global([
        SoloUserRequest::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            status: 200,
        ),
    ]);

    $response = (new SoloUserRequest)->send();

    expect($response->status())->toBe(200);
    expect($response->json())->toBe([
        'data' => [
            ['name' => 'John Wayne'],
            ['name' => 'Billy the Kid'],
        ],
    ]);

    assertDatabaseCount('barstools', 1);
    assertDatabaseHas('barstools', [
        'connector_class' => NullConnector::class,
        'request_class' => SoloUserRequest::class,
        'method' => 'GET',
        'url' => 'https://tests.saloon.dev/api/user',
        'response_status' => 200,
        'successful' => true,
    ]);
});

it('can be disabled', function () {
    config()->set('barstool.enabled', false);

    MockClient::global([
        SoloUserRequest::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            status: 200,
        ),
    ]);

    $response = (new SoloUserRequest)->send();
    expect($response->status())->toBe(200);

    assertDatabaseCount('barstools', 0);
});

it('can change the database connection', function () {
    expect(Barstool::make()->getConnectionName())->toBe('mysql');

    config()->set('barstool.connection', 'sqlite');
    config()->set('database.connections.sqlite', [
        'driver' => 'sqlite',
        'database' => ':memory:',
    ]);

    expect(Barstool::make()->getConnectionName())->toBe('sqlite');
});

it('can change the number of days to keep recordings for', function () {
    // Check the default value is 30
    expect(config('barstool.keep_for_days'))->toBe(30);

    $this->travel(-10)->days();
    Barstool::factory()->count(2)->create();

    // Travel back another -25 days making the total 35 days
    $this->travel(-25)->days();
    Barstool::factory()->count(3)->create();

    assertDatabaseCount('barstools', 5);

    $this->travelBack();
    Artisan::call('model:prune', ['--model' => [Barstool::class]]);

    assertDatabaseCount('barstools', 2);

    config()->set('barstool.keep_for_days', 5);
    expect(config('barstool.keep_for_days'))->toBe(5);

    Artisan::call('model:prune', ['--model' => [Barstool::class]]);

    assertDatabaseEmpty('barstools');
});

it('does not log requests, responses or fatal on an excluded request', function () {
    config()->set('barstool.enabled', true);
    config()->set('barstool.ignore.requests', [RequestWithConnector::class]);

    MockClient::global([
        SoloUserRequest::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            status: 200,
        ),
        RequestWithConnector::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'Daisy Dot'],
                    ['name' => 'Pistol Pete'],
                ],
            ],
            status: 200,
        ),
    ]);

    $response = (new SoloUserRequest)->send();

    expect($response->status())->toBe(200);
    expect($response->json())->toBe([
        'data' => [
            ['name' => 'John Wayne'],
            ['name' => 'Billy the Kid'],
        ],
    ]);

    assertDatabaseCount('barstools', 1);

    $connector = new RandomConnector;
    $response = $connector->send(new RequestWithConnector);

    expect($response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->toBeNull();

    expect($response->status())->toBe(200);
    expect($response->json())->toBe([
        'data' => [
            ['name' => 'Daisy Dot'],
            ['name' => 'Pistol Pete'],
        ],
    ]);

    MockClient::global([
        RequestWithConnector::class => MockResponse::make(['error' => 'whoops'], 500)->throw(fn ($pendingRequest) => new FatalRequestException(new Exception, $pendingRequest)),
    ]);

    try {
        $connector = new RandomConnector;
        $connector->send(new RequestWithConnector);
    } catch (FatalRequestException $e) {
        expect($e->getPendingRequest()->headers()->get('X-Barstool-UUID'))->toBeNull();
    }

    assertDatabaseCount('barstools', 1);
});

it('does not log requests, responses or fatal on an excluded connector', function () {
    config()->set('barstool.enabled', true);
    config()->set('barstool.ignore.connectors', [NullConnector::class]);

    MockClient::global([
        SoloUserRequest::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            status: 200,
        ),
        RequestWithConnector::class => MockResponse::make(['error' => 'whoops'], 500)->throw(fn ($pendingRequest) => new FatalRequestException(new Exception, $pendingRequest)),
    ]);

    $response = (new SoloUserRequest)->send();

    expect($response->status())->toBe(200);
    expect($response->json())->toBe([
        'data' => [
            ['name' => 'John Wayne'],
            ['name' => 'Billy the Kid'],
        ],
    ]);

    assertDatabaseCount('barstools', 0);

    try {
        $connector = new RandomConnector;
        $connector->send(new RequestWithConnector);
    } catch (FatalRequestException $e) {
        expect($e->getPendingRequest()->headers()->get('X-Barstool-UUID'))->not()->toBeNull()->toBeString();
    }

    assertDatabaseCount('barstools', 1);

});

it('correctly records headers', function () {
    MockClient::global([
        RequestWithConnector::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            headers: ['token' => 'abc123'],
            status: 200,
        ),
    ]);

    $connector = new RandomConnector;
    $request = new RequestWithConnector;
    $request->headers()->add('some-secret', 'yeehaw');
    $response = $connector->send($request);

    expect($response->status())->toBe(200);
    expect($response->json())->toBe([
        'data' => [
            ['name' => 'John Wayne'],
            ['name' => 'Billy the Kid'],
        ],
    ]);
    expect($response->headers()->get('token'))->toBe('abc123');

    $request = $response->getPendingRequest();
    $requestHeaders = [
        'testing' => 'headers',
        'some-secret' => 'yeehaw',
        'X-Barstool-UUID' => $uuid = $request->headers()->get('X-Barstool-UUID'),
    ];
    expect($request->headers()->all())->toBe($requestHeaders);

    assertDatabaseCount('barstools', 1);

    $barstool = Barstool::where('uuid', $uuid)->sole();
    expect($barstool->request_headers)->toBe($requestHeaders);
    expect($response->headers()->all())->toBe(['token' => 'abc123']);
    expect($barstool->response_headers)->toBe(['token' => 'abc123']);
});

it('correctly records body, query, status & method', function () {
    MockClient::global([
        RequestWithConnector::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            headers: ['token' => 'abc123', 'Content-Type' => 'application/json'],
            status: 200,
        ),
        PostRequest::class => MockResponse::make(
            body: [],
            status: 201,
            headers: ['Content-Type' => 'application/json'],
        ),
        GetFileRequest::class => MockResponse::fixture('get-file'),
    ]);

    $connector = new RandomConnector;
    $request = new RequestWithConnector;
    $request->headers()->add('some-secret', 'yeehaw');
    $request->query()->add('page', 500);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->method->toBe('GET')
        ->url->toBe('https://tests.saloon.dev/api/user?page=500')
        ->request_headers->toBe([
            'testing' => 'headers',
            'some-secret' => 'yeehaw',
            'X-Barstool-UUID' => $barstool->uuid,
        ])
        ->request_body->toBeNull()
        ->response_status->toBe(200)
        ->successful->toBeTrue()
        ->response_headers->toBe(['token' => 'abc123', 'Content-Type' => 'application/json'])
        ->response_body->toBe(json_encode([
            'data' => [
                ['name' => 'John Wayne'],
                ['name' => 'Billy the Kid'],
            ],
        ]));

    $request = new PostRequest;
    $request->body()->set(fopen(__DIR__.'/yeehaw.txt', 'r'));
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();

    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(PostRequest::class)
        ->method->toBe('POST')
        ->url->toBe('https://craigpotter-not-real.dev/user')
        ->request_headers->toBe([
            'Content-Type' => 'text/plain',
            'X-Barstool-UUID' => $barstool->uuid,
        ])
        ->request_body->toBe('<Streamed Body>')
        ->response_status->toBe(201)
        ->successful->toBeTrue()
        ->response_headers->toBe(['Content-Type' => 'application/json'])
        ->response_body->toBe('[]');

    $request = new GetFileRequest;
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();

    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(GetFileRequest::class)
        ->method->toBe('GET')
        ->url->toBe('https://http.cat/images/418.jpg')
        ->request_headers->toBe([
            'X-Barstool-UUID' => $barstool->uuid,
        ])
        ->request_body->toBeEmpty()
        ->response_status->toBe(200)
        ->successful->toBeTrue()
        ->response_headers->toBe([
            'Age' => '68',
            'NEL' => '{"success_fraction":0,"report_to":"cf-nel","max_age":604800}',
            'Date' => 'Thu, 10 Oct 2024 19:17:35 GMT',
            'etag' => '"668d36cb-556f"',
            'CF-RAY' => '8d08f3915a8f60ef-LHR',
            'Server' => 'cloudflare',
            'alt-svc' => 'h3=":443"; ma=86400',
            'expires' => 'Thu, 31 Dec 2037 23:55:55 GMT',
            'Report-To' => '{"endpoints":[{"url":"https:\/\/a.nel.cloudflare.com\/report\/v4?s=lgJFDE1PZieCSO4IK5BWwV0I%2BNSvuveEgh11FqjElE3FcHg3kDkKmg29j8Y5tC4hGZZesZc7T8Gs8R53GcPR69G9ypdkimPz%2F2uPdYo1wBjfDxc%2FPIz9lwpZzw%3D%3D"}],"group":"cf-nel","max_age":604800}',
            'Connection' => 'keep-alive',
            'Content-Type' => 'image/jpeg',
            'Accept-Ranges' => 'bytes',
            'Cache-Control' => 'max-age=315360000',
            'last-modified' => 'Tue, 09 Jul 2024 13:10:35 GMT',
            'Content-Length' => '21871',
            'CF-Cache-Status' => 'HIT',
        ])
        ->response_body->toBe('<Unsupported Barstool Response Content>');
});

it('can exclude request headers for all request, certain headers,entire connectors or entire requests', function () {
    MockClient::global([
        RequestWithConnector::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            status: 200,
            headers: ['token' => 'abc123', 'Content-Type' => 'application/json'],
        ),
    ]);

    $connector = new RandomConnector;
    $request = new RequestWithConnector;
    $request->headers()->add('some-secret', 'yeehaw');

    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->request_headers->toBe([
            'testing' => 'headers',
            'some-secret' => 'yeehaw',
            'X-Barstool-UUID' => $barstool->uuid,
        ]);

    // Exclude all headers - should still get the X-Barstool-UUID
    config()->set('barstool.excluded_request_headers', ['*']);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->request_headers->toBe([
            'X-Barstool-UUID' => $barstool->uuid,
        ]);

    // Exclude 'some-secret' header
    config()->set('barstool.excluded_request_headers', ['some-secret']);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->request_headers->toBe([
            'testing' => 'headers',
            'some-secret' => 'REDACTED',
            'X-Barstool-UUID' => $barstool->uuid,
        ]);

    // Exclude the Request
    config()->set('barstool.excluded_request_headers', [RequestWithConnector::class]);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->request_headers->toBe([
            'X-Barstool-UUID' => $barstool->uuid,
        ]);

    // Exclude the connector
    config()->set('barstool.excluded_request_headers', [RandomConnector::class]);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->request_headers->toBe([
            'X-Barstool-UUID' => $barstool->uuid,
        ]);
});

it('can exclude response bodies for all responses, centire connectors or entire requests', function () {
    MockClient::global([
        RequestWithConnector::class => MockResponse::make(
            body: [
                'data' => [
                    ['name' => 'John Wayne'],
                    ['name' => 'Billy the Kid'],
                ],
            ],
            status: 200,
            headers: ['token' => 'abc123', 'Content-Type' => 'application/json'],
        ),
    ]);

    $connector = new RandomConnector;
    $request = new RequestWithConnector;

    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe(json_encode([
            'data' => [
                ['name' => 'John Wayne'],
                ['name' => 'Billy the Kid'],
            ],
        ]));

    // Exclude all bodies - should still get the X-Barstool-UUID
    config()->set('barstool.excluded_response_body', ['*']);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe('REDACTED');

    // Exclude the Request
    config()->set('barstool.excluded_response_body', [RequestWithConnector::class]);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe('REDACTED');

    // Exclude the connector
    config()->set('barstool.excluded_response_body', [RandomConnector::class]);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe('REDACTED');
});

it('can store response bodies where the kilobytes are below a config value', function () {
    $responseBody = ['data' => ['body' => Str::random(10000)]];
    $kilobytes = intdiv(mb_strlen(json_encode($responseBody)), 1000); // 10KB

    MockClient::global([
        RequestWithConnector::class => MockResponse::make(
            body: $responseBody,
            status: 200,
            headers: ['token' => 'abc123', 'Content-Type' => 'application/json'],
        ),
    ]);

    $connector = new RandomConnector;
    $request = new RequestWithConnector;

    $response = $connector->send($request);

    // Check the response body is stored when using the default config
    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe(json_encode($responseBody));

    // Check the response body is not stored when the kilobytes are above the config value
    config()->set('barstool.max_response_size', $kilobytes - 1);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe('<Unsupported Barstool Response Content>');

    // Check the response body is stored when the kilobytes are below the config value
    config()->set('barstool.max_response_size', $kilobytes + 1);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe(json_encode($responseBody));

    // Check the response body is stored when the kilobytes are equal to the config value
    config()->set('barstool.max_response_size', $kilobytes);
    $response = $connector->send($request);

    $barstool = Barstool::where('uuid', $response->getPendingRequest()->headers()->get('X-Barstool-UUID'))->sole();
    expect($barstool)
        ->connector_class->toBe(RandomConnector::class)
        ->request_class->toBe(RequestWithConnector::class)
        ->response_body->toBe(json_encode($responseBody));

});
