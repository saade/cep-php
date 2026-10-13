<?php

use Saade\Cep\Cep;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\BrasilApiProvider;
use Saade\Cep\Providers\CorreiosAltProvider;
use Saade\Cep\Providers\CorreiosProvider;
use Saade\Cep\Providers\OpenCepProvider;
use Saade\Cep\Providers\ViaCepProvider;
use Saade\Cep\Providers\WideNetProvider;

const FALLBACK_PROVIDERS = [
    CorreiosProvider::class,
    ViaCepProvider::class,
    WideNetProvider::class,
    CorreiosAltProvider::class,
    BrasilApiProvider::class,
];

it('resolves a real cep', function () {
    expect(Cep::get('01001-000'))
        ->toBeInstanceOf(CepResponse::class)
        ->cep->toBe('01001000')
        ->street->toBe('Praça da Sé')
        ->neighborhood->toBe('Sé')
        ->city->toBe('São Paulo')
        ->state->toBe('SP');
});

it('resolves a real cep through the fallback race', function () {
    expect(Cep::race('01001-000', FALLBACK_PROVIDERS))
        ->toBeInstanceOf(CepResponse::class)
        ->cep->toBe('01001000')
        ->city->toBe('São Paulo')
        ->state->toBe('SP');
});

it('returns null for a cep that does not exist', function () {
    expect(Cep::get('00000-000'))->toBeNull();
});

it('resolves the right address whenever the provider is available', function (string $provider) {
    try {
        $response = $provider::get('01001-000');
    } catch (Throwable $exception) {
        $this->markTestSkipped("Unavailable from this network: {$exception->getMessage()}");
    }

    expect($response)
        ->toBeInstanceOf(CepResponse::class)
        ->cep->toBe('01001000')
        ->street->toStartWith('Praça da Sé')
        ->neighborhood->toBe('Sé')
        ->city->toBe('São Paulo')
        ->state->toBe('SP');
})->with([
    'open-cep' => OpenCepProvider::class,
    'correios' => CorreiosProvider::class,
    'viacep' => ViaCepProvider::class,
    'widenet' => WideNetProvider::class,
    'correios-alt' => CorreiosAltProvider::class,
    'brasil-api' => BrasilApiProvider::class,
]);
