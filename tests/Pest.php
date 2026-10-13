<?php

use Orchestra\Testbench\TestCase;
use Saloon\Config;
use Saloon\Http\Faking\MockClient;

pest()->beforeEach(function () {
    MockClient::destroyGlobal();
    Config::clearGlobalMiddleware();
    Config::preventStrayRequests();
})->in('Unit');

pest()->beforeEach(function () {
    MockClient::destroyGlobal();
    Config::clearGlobalMiddleware();
    Config::allowStrayRequests();
})->in('Live', 'Integration');

pest()->extend(TestCase::class)->in('Laravel');

function stub(string $file): string
{
    return file_get_contents(fixture($file));
}
