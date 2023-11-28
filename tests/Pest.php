<?php

use Saloon\Config;
use Saloon\Http\Faking\MockClient;
use CraigPotter\Barstool\Tests\TestCase;

uses(TestCase::class)->in(__DIR__);

uses()
    ->beforeEach(function () {
        MockClient::destroyGlobal();
    })
    ->afterEach(function () {
        Config::clearGlobalMiddleware();
    })
    ->in(__DIR__);
