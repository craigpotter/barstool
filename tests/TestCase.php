<?php

declare(strict_types=1);

namespace CraigPotter\Barstool\Tests;

use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use CraigPotter\Barstool\BarstoolServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'CraigPotter\\Barstool\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            BarstoolServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        Schema::dropAllTables();

        $migration = include __DIR__.'/../database/migrations/create_barstools_table.php.stub';
        $migration->up();

    }
}
