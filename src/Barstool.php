<?php

declare(strict_types=1);

namespace CraigPotter\Barstool;

use Saloon\Http\Response;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Saloon\Http\PendingRequest;
use Psr\Http\Message\UriInterface;
use Saloon\Contracts\Body\BodyRepository;
use Saloon\Repositories\Body\StreamBodyRepository;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Repositories\Body\MultipartBodyRepository;

class Barstool
{
    public static function shouldRecord(PendingRequest|Response|FatalRequestException $data): bool
    {
        if (config('barstool.enabled') !== true) {
            return false;
        }

        [$connector, $request] = match (true) {
            is_a($data, PendingRequest::class) => [$data->getConnector(), $data->getRequest()],
            is_a($data, Response::class), is_a($data, FatalRequestException::class) => [$data->getPendingRequest()->getConnector(), $data->getPendingRequest()->getRequest()],
            default => throw new InvalidArgumentException('Invalid data type provided to shouldRecord')
        };

        if (in_array(get_class($connector), config('barstool.ignore.connectors', []))) {
            return false;
        }

        if (in_array(get_class($request), config('barstool.ignore.requests', []))) {
            return false;
        }

        return true;
    }

    public static function record(PendingRequest|Response|FatalRequestException $data): void
    {
        match (true) {
            is_a($data, PendingRequest::class) => self::recordRequest($data),
            is_a($data, Response::class) => self::recordResponse($data),
            is_a($data, FatalRequestException::class) => self::recordFatal($data),
            default => null
        };
    }

    /**
     * @return array{
     *      connector_class: class-string,
     *      request_class: class-string,
     *      method: string,
     *      url: string,
     *      request_headers: array<string, string>|null,
     *      request_body: BodyRepository|string|null,
     *      successful: false
     * }
     */
    private static function getRequestData(PendingRequest $request): array
    {
        $body = $request->body();

        $body = match (true) {
            $body instanceof StreamBodyRepository => '<Streamed Body>',
            $body instanceof MultipartBodyRepository => '<Multipart Body>',
            default => $body,
        };

        return [
            'connector_class' => get_class($request->getConnector()),
            'request_class' => get_class($request->getRequest()),
            'method' => $request->getMethod()->value,
            'url' => $request->getUrl(),
            'request_headers' => self::getRequestHeaders($request),
            'request_body' => $body,
            'successful' => false,
        ];
    }

    /**
     * @return array{
     *      url: UriInterface,
     *      status: 'failed'|'successful',
     *      response_headers: array<string, mixed>,
     *      response_body: string,
     *      response_status: int,
     *      successful: bool
     * }
     */
    private static function getResponseData(Response $response): array
    {
        $responseBody = self::getResponseBody($response);

        return [
            'url' => $response->getPsrRequest()->getUri(),
            'status' => $response->failed() ? 'failed' : 'successful',
            'response_headers' => $response->headers()->all(),
            'response_body' => $responseBody,
            'response_status' => $response->status(),
            'successful' => $response->successful(),
        ];
    }

    /**
     * @return array{
     *      url: UriInterface,
     *      status: 'fatal',
     *      response_headers: null,
     *      response_body: null,
     *      response_status: null,
     *      successful: false,
     *      fatal_error: string
     * }
     */
    private static function getFatalData(FatalRequestException $exception): array
    {
        return [
            'url' => $exception->getPendingRequest()->getUri(),
            'status' => 'fatal',
            'response_headers' => null,
            'response_body' => null,
            'response_status' => null,
            'successful' => false,
            'fatal_error' => $exception->getMessage(),
        ];
    }

    private static function recordRequest(PendingRequest $data): void
    {
        $uuid = Str::uuid()->toString();

        $data->headers()->add('X-Barstool-UUID', $uuid);

        $entry = new Models\Barstool;
        $entry->uuid = $uuid;
        $entry->fill([...self::getRequestData($data)]);
        $entry->save();
    }

    private static function recordResponse(Response $data): void
    {
        $psrRequest = $data->getPsrRequest();

        $uuid = $psrRequest->getHeader('X-Barstool-UUID')[0] ?? null;
        if (is_null($uuid)) {
            return;
        }

        $entry = Models\Barstool::query()->firstWhere('uuid', $uuid);

        if ($entry) {
            $entry->fill([
                'duration' => self::calculateDuration($data),
                ...self::getResponseData($data),
            ]);
            $entry->save();
        }
    }

    public static function calculateDuration(Response|PendingRequest $data): int
    {
        $config = $data->getConnector()->config();

        $requestTime = (int) $config->get('barstool-request-time');
        $responseTime = (int) $config->get('barstool-response-time', microtime(true) * 1000);

        return $responseTime - $requestTime;
    }

    private static function recordFatal(FatalRequestException $data): void
    {
        $pendingRequest = $data->getPendingRequest();
        $uuid = $pendingRequest->headers()->get('X-Barstool-UUID');

        $entry = Models\Barstool::query()->firstWhere('uuid', $uuid);

        if ($entry) {
            $entry->fill([
                'duration' => self::calculateDuration($pendingRequest),
                ...self::getFatalData($data),
            ]);
            $entry->save();
        }
    }

    /**
     * Get the supported content types for response bodies.
     *
     * @return string[]
     */
    private static function supportedContentTypes(): array
    {
        return [
            'application/json',
            'application/xml',
            'text/xml',
            'text/html',
            'text/plain',
        ];
    }

    /**
     * @return array<string, string>|null
     */
    public static function getRequestHeaders(PendingRequest $request): ?array
    {
        $excludedHeaders = config('barstool.excluded_request_headers', []);
        $headers = collect($request->headers()->all());

        // Check if all headers are excluded
        if (in_array('*', $excludedHeaders)) {
            return $headers->reject(fn ($value, $key) => $key !== 'X-Barstool-UUID')->toArray();
        }

        // Check if the connector class is excluded
        if (in_array(get_class($request->getConnector()), $excludedHeaders)) {
            return $headers->reject(fn ($value, $key) => $key !== 'X-Barstool-UUID')->toArray();
        }

        // Check if the request class is excluded
        if (in_array(get_class($request->getRequest()), $excludedHeaders)) {
            return $headers->reject(fn ($value, $key) => $key !== 'X-Barstool-UUID')->toArray();
        }

        return $headers->map(function ($value, $key) use ($excludedHeaders) {
            if (in_array($key, $excludedHeaders)) {
                $value = 'REDACTED';
            }

            return $value;
        })->toArray();
    }

    public static function getResponseBody(Response $response): string
    {
        $excludedBodies = config('barstool.excluded_response_body', []);

        // Check if all bodies are excluded
        if (in_array('*', $excludedBodies)) {
            return 'REDACTED';
        }

        // Check if the connector class is excluded
        if (in_array(get_class($response->getConnector()), $excludedBodies)) {
            return 'REDACTED';
        }

        // Check if the request class is excluded
        if (in_array(get_class($response->getRequest()), $excludedBodies)) {
            return 'REDACTED';
        }

        $body = $response->body();

        if (Str::startsWith(mb_strtolower((string) $response->headers()->get('Content-Type')), self::supportedContentTypes())) {
            return self::checkContentSize($body) ? $body : '<Unsupported Barstool Response Content>';
        }

        return '<Unsupported Barstool Response Content>';
    }

    /**
     * Check if the content is within limits
     */
    private static function checkContentSize(mixed $body): bool
    {
        try {
            $body = (string) $body;

            return intdiv(mb_strlen($body), 1000) <= config('barstool.max_response_size', 100);
        } catch (\Throwable) {
            return false;
        }
    }
}
