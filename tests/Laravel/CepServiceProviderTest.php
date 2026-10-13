<?php

use Saade\Cep\CepServiceProvider;

it('registers in a Laravel application', function () {
    $this->app->register(CepServiceProvider::class);

    expect($this->app->getProvider(CepServiceProvider::class))->toBeInstanceOf(CepServiceProvider::class);
});

it('exposes an existing provider for package discovery', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    expect($composer['extra']['laravel']['providers'])
        ->toBe([CepServiceProvider::class])
        ->each(fn ($provider) => $provider->toBeClass());
});
