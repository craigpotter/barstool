<?php

namespace CraigPotter\Barstool;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CraigPotter\Barstool\Commands\BarstoolCommand;

class BarstoolServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('barstool')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_barstool_table')
            ->hasCommand(BarstoolCommand::class);
    }
}
