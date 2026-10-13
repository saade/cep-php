<?php

use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\BrasilApiProvider;

it('maps a found address', function () {
    fakeProviders(['brasilapi.com.br' => reply(stub('brasil-api/found.json'))]);

    expect(BrasilApiProvider::get('28895-190'))
        ->toBeInstanceOf(CepResponse::class)
        ->toArray()->toBe([
            'provider' => 'brasil-api',
            'cep' => '28895190',
            'street' => 'Rua Nelson Pecegueiro do Amaral',
            'neighborhood' => 'Costazul',
            'city' => 'Rio das Ostras',
            'state' => 'RJ',
        ]);
});

it('requests the sanitized cep', function () {
    $fake = fakeProviders(['brasilapi.com.br' => reply(stub('brasil-api/found.json'))]);

    BrasilApiProvider::get('28895-190');

    [$request, $options] = $fake->sent[0];

    expect($request->getMethod())->toBe('GET')
        ->and((string) $request->getUri())->toBe('https://brasilapi.com.br/api/cep/v1/28895190')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($options['timeout'])->toEqual(5);
});

it('fails when the cep cannot be resolved', function (Response $response) {
    fakeProviders(['brasilapi.com.br' => $response]);

    expect(fn () => BrasilApiProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => reply(stub('brasil-api/not-found.json'), 404),
    'validation error' => fn () => reply(['type' => 'validation_error'], 400),
    'server error' => fn () => reply('', 500),
    'empty body' => fn () => reply(''),
    'html body' => fn () => reply('<html></html>'),
    'json without an address' => fn () => reply(['state' => 'RJ']),
]);

it('resolves asynchronously', function () {
    fakeProviders(['brasilapi.com.br' => reply(stub('brasil-api/found.json'))]);

    expect(BrasilApiProvider::getAsync('28895-190')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('brasil-api')
        ->cep->toBe('28895190');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    fakeProviders(['brasilapi.com.br' => reply(stub('brasil-api/not-found.json'), 404)]);

    expect(fn () => BrasilApiProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
