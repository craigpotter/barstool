<?php

namespace CraigPotter\Barstool;

use Illuminate\Support\Facades\Event;
use Saloon\Laravel\Events\SentSaloonRequest;
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
        Event::listen(SentSaloonRequest::class, function (SentSaloonRequest $request) {
            Barstool::record($request);
        });
    }
}
