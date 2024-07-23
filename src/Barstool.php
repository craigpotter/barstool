<?php

namespace CraigPotter\Barstool;

use Illuminate\Support\Str;
use Saloon\Http\Response;
use Saloon\Http\PendingRequest;
use Saloon\Laravel\Events\SentSaloonRequest;

class Barstool
{
    public static function record(PendingRequest|Response $data)
    {
        if(! config('barstool.enabled')) {
            return;
        }

        if($data instanceof PendingRequest) {
            self::recordRequest($data);
        } else {
            self::recordResponse($data);
        }
//
//        $entry = new Models\Barstool();
//        $entry->fill([...self::getRequestData($request->pendingRequest), ...self::getResponseData($request->response)]);
//        $entry->save();
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
            'successful' => false
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

    private static function recordRequest(PendingRequest $data)
    {
        $uuid = Str::uuid()->toString();

        $data->headers()->add('X-Barstool-UUID', $uuid);

        $entry = new Models\Barstool();
        $entry->uuid = $uuid;
        $entry->fill([...self::getRequestData($data)]);
        $entry->save();
    }

    private static function recordResponse(Response|PendingRequest $data)
    {
        ray()->showQueries();

        $uuid = $data->getPsrRequest()->getHeader('X-Barstool-UUID')[0];

        $entry = Models\Barstool::where('uuid', $uuid)->first();

        if($entry) {
            $entry->fill([
                'duration' => self::calculateDuration($data),
                ...self::getResponseData($data)
            ]);
            $entry->save();
        }
    }

    /**
     * @param Response|PendingRequest $data
     * @return mixed
     */
    public static function calculateDuration(Response|PendingRequest $data): mixed
    {
        return $data->getConnector()->config()->get('barstool-response-time') - $data->getConnector()->config()->get('barstool-request-time');
    }
}
