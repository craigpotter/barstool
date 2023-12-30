<?php

namespace CraigPotter\Barstool;

use Saloon\Http\Response;
use Saloon\Http\PendingRequest;
use Saloon\Laravel\Events\SentSaloonRequest;

class Barstool
{
    public static function record(SentSaloonRequest $request)
    {
        if(! config('barstool.enabled')) {
            return;
        }

        $entry = new Models\Barstool();
        $entry->fill([...self::getRequestData($request->pendingRequest), ...self::getResponseData($request->response)]);
        $entry->save();
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
        ];
    }

    private static function getResponseData(Response $response)
    {
        if(! config('keep_successful_responses') && $response->failed()) {
            return null;
        }

        return [
            'status' => $response->failed() ? 'failed' : 'successful',
            'response_headers' => $response->headers()->all(),
            'response_body' => $response->body(),
            'response_status' => $response->status(),
            'successful' => $response->successful(),
        ];
    }
}
