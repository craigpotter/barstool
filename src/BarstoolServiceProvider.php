<?php

namespace CraigPotter\Barstool;

use Saloon\Config;
use Saloon\Enums\PipeOrder;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\PendingRequest;
use Saloon\Http\Response;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
        Config::globalMiddleware()
            ->onFatalException(function (FatalRequestException $exception) {

                ray('Fatal exception intercepted', $exception);

                Barstool::record($exception);

            }, 'test', PipeOrder::FIRST)
            ->onRequest(function (PendingRequest $request) {
                ray('Request intercepted!!!!', $request);
                $request->getConnector()->config()->add(
                    'barstool-request-time',
                    microtime(true) * 1000
                );

                Barstool::record($request);
            })

            ->onResponse(function (Response $response) {
                ray('Response intercepted', $response);

                $response->getConnector()->config()->add(
                    'barstool-response-time',
                    microtime(true) * 1000
                );

                if ($response->successful() && config('barstool.keep_successful_responses') === false) {
                    return;
                }

                Barstool::record($response);
            });

    }
}
