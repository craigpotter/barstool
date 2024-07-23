<?php

namespace CraigPotter\Barstool;

use GuzzleHttp\TransferStats;
use Illuminate\Support\Facades\Event;
use Saloon\Config;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Saloon\Laravel\Events\SentSaloonRequest;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CraigPotter\Barstool\Commands\BarstoolCommand;

class BarstoolServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('barstool')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_barstools_table');
    }

    public function packageBooted()
    {
        ray()->clearAll();

//        Event::listen(SentSaloonRequest::class, function (SentSaloonRequest $request) {
//            ray($request);
//            Barstool::record($request);
//        });



        Config::globalMiddleware()->onRequest(function (PendingRequest $request) {
            ray('Request intercepted', $request);
            $request->getConnector()->config()->add(
                'barstool-request-time',
                microtime(true) * 1000
            );

            Barstool::record($request);
        });

        Config::globalMiddleware()->onResponse(function (Response $response) {
            ray('Response intercepted', $response);

            $response->getConnector()->config()->add(
                'barstool-response-time',
                microtime(true) * 1000
            );

            Barstool::record($response);
        });
    }
}
