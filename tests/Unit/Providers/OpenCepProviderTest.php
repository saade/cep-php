<?php

use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\OpenCepProvider;

it('maps a found address', function () {
    fakeProviders(['opencep.com' => reply(stub('opencep/found.json'))]);

    expect(OpenCepProvider::get('28895-190'))
        ->toBeInstanceOf(CepResponse::class)
        ->toArray()->toBe([
            'provider' => 'open-cep',
            'cep' => '28895190',
            'street' => 'Rua Nelson Pecegueiro do Amaral',
            'neighborhood' => 'Costazul',
            'city' => 'Rio das Ostras',
            'state' => 'RJ',
        ]);
});

it('requests the sanitized cep', function () {
    $fake = fakeProviders(['opencep.com' => reply(stub('opencep/found.json'))]);

    OpenCepProvider::get('28895-190');

    [$request, $options] = $fake->sent[0];

    expect($request->getMethod())->toBe('GET')
        ->and((string) $request->getUri())->toBe('https://opencep.com/v1/28895190')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($options['timeout'])->toEqual(3);
});

it('fails when the cep cannot be resolved', function (Response $response) {
    fakeProviders(['opencep.com' => $response]);

    expect(fn () => OpenCepProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => reply(stub('opencep/not-found.json'), 404),
    'server error' => fn () => reply('', 500),
    'empty body' => fn () => reply(''),
    'html body' => fn () => reply('<html></html>'),
    'json without an address' => fn () => reply(['error' => true]),
]);

it('resolves asynchronously', function () {
    fakeProviders(['opencep.com' => reply(stub('opencep/found.json'))]);

    expect(OpenCepProvider::getAsync('28895-190')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('open-cep')
        ->cep->toBe('28895190');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    fakeProviders(['opencep.com' => reply(stub('opencep/not-found.json'), 404)]);

    expect(fn () => OpenCepProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
