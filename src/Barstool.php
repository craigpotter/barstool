<?php

namespace CraigPotter\Barstool;

use Illuminate\Support\Str;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;

class Barstool
{
    public static function record(PendingRequest|Response|FatalRequestException $data): void
    {
        if (! config('barstool.enabled')) {
            return;
        }

        match (true) {
            is_a($data, PendingRequest::class) => self::recordRequest($data),
            is_a($data, Response::class) => self::recordResponse($data),
            is_a($data, FatalRequestException::class) => self::recordFatal($data),
        };

    }

    private static function getRequestData(PendingRequest $request)
    {
        return [
            'connector_class' => get_class($request->getConnector()),
            'request_class' => get_class($request->getRequest()),
            'method' => $request->getMethod()?->value,
            'url' => $request->getUrl(),
            'request_headers' => $request->headers()->all(),
            'request_body' => $request->body(),
            'successful' => false,
        ];
    }

    private static function getResponseData(Response $response)
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

    private static function getFatalData(FatalRequestException $exception)
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

    private static function recordRequest(PendingRequest $data)
    {
        $uuid = Str::uuid()->toString();

        $data->headers()->add('X-Barstool-UUID', $uuid);

        $entry = new Models\Barstool;
        $entry->uuid = $uuid;
        $entry->fill([...self::getRequestData($data)]);
        $entry->save();
    }

    private static function recordResponse(Response|PendingRequest $data)
    {
        ray()->showQueries();

        $uuid = $data->getPsrRequest()->getHeader('X-Barstool-UUID')[0];

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

    private static function recordFatal(FatalRequestException $data)
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
