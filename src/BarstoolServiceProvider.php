<?php

namespace CraigPotter\Barstool;

use Saloon\Config;
use Saloon\Http\Response;
use Saloon\Enums\PipeOrder;
use Saloon\Http\PendingRequest;
use Spatie\LaravelPackageTools\Package;
use Saloon\Exceptions\Request\FatalRequestException;
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

    public function packageRegistered()
    {
        Config::globalMiddleware()
            ->onFatalException(function (FatalRequestException $exception) {
                if (Barstool::shouldRecord($exception) === false) {
                    return;
                }

                Barstool::record($exception);

            }, order: PipeOrder::FIRST)
            ->onRequest(function (PendingRequest $request) {
                if (Barstool::shouldRecord($request) === false) {
                    return;
                }

                $request->getConnector()->config()->add(
                    'barstool-request-time',
                    microtime(true) * 1000
                );

                Barstool::record($request);
            })
            ->onResponse(function (Response $response) {
                if (Barstool::shouldRecord($response) === false) {
                    return;
                }

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
