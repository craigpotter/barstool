<?php

namespace CraigPotter\Barstool;

use Saloon\Http\Response;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Saloon\Http\PendingRequest;
use Saloon\Repositories\Body\StreamBodyRepository;
use Saloon\Exceptions\Request\FatalRequestException;

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

    private static function getRequestData(PendingRequest $request): array
    {
        $body = $request->body();

        if ($body instanceof StreamBodyRepository) {
            $body = '<Streamed Body>';
        }

        return [
            'connector_class' => get_class($request->getConnector()),
            'request_class' => get_class($request->getRequest()),
            'method' => $request->getMethod()->value,
            'url' => $request->getUrl(),
            'request_headers' => $request->headers()->all(),
            'request_body' => $body,
            'successful' => false,
        ];
    }

    private static function getResponseData(Response $response): array
    {
        return [
            'url' => $response->getPsrRequest()->getUri(),
            'status' => $response->failed() ? 'failed' : 'successful',
            'response_headers' => $response->headers()->all(),
            'response_body' => $response->body(),
            'response_status' => $response->status(),
            'successful' => $response->successful(),
        ];
    }

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

    private static function recordResponse(Response|PendingRequest $data): void
    {
        $uuid = $data->getPsrRequest()->getHeader('X-Barstool-UUID')[0] ?? null;
        if (is_null($uuid)) {
            return;
        }

        $entry = Models\Barstool::where('uuid', $uuid)->first();

        if ($entry) {
            $entry->fill([
                'duration' => self::calculateDuration($data),
                ...self::getResponseData($data),
            ]);
            $entry->save();
        }
    }

    /**
     * @param  Response|PendingRequest  $data
     */
    public static function calculateDuration(Response|PendingRequest|FatalRequestException $data): mixed
    {
        return $data->getConnector()->config()->get('barstool-response-time', microtime(true) * 1000) - $data->getConnector()->config()->get('barstool-request-time');
    }

    private static function recordFatal(FatalRequestException $data): void
    {
        $pendingRequest = $data->getPendingRequest();
        $uuid = $pendingRequest->headers()->get('X-Barstool-UUID');

        $entry = Models\Barstool::where('uuid', $uuid)->first();

        if ($entry) {
            $entry->fill([
                'duration' => self::calculateDuration($pendingRequest),
                ...self::getFatalData($data),
            ]);
            $entry->save();
        }
    }
}
