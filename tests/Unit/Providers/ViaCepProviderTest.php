<?php

use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\ViaCepProvider;

it('maps a found address', function () {
    fakeProviders(['viacep.com.br' => reply(stub('viacep/found.json'))]);

    expect(ViaCepProvider::get('28895-190'))
        ->toBeInstanceOf(CepResponse::class)
        ->toArray()->toBe([
            'provider' => 'viacep',
            'cep' => '28895190',
            'street' => 'Rua Nelson Pecegueiro do Amaral',
            'neighborhood' => 'Costazul',
            'city' => 'Rio das Ostras',
            'state' => 'RJ',
        ]);
});

it('requests the sanitized cep', function () {
    $fake = fakeProviders(['viacep.com.br' => reply(stub('viacep/found.json'))]);

    ViaCepProvider::get('28895-190');

    [$request, $options] = $fake->sent[0];

    expect($request->getMethod())->toBe('GET')
        ->and((string) $request->getUri())->toBe('https://viacep.com.br/ws/28895190/json/')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($options['timeout'])->toEqual(5);
});

it('fails when the cep cannot be resolved', function (Response $response) {
    fakeProviders(['viacep.com.br' => $response]);

    expect(fn () => ViaCepProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => reply(stub('viacep/not-found.json')),
    'not found with boolean flag' => fn () => reply(['erro' => true]),
    'bad request' => fn () => reply('<h2>Bad Request (400)</h2>', 400),
    'server error' => fn () => reply('', 500),
    'empty body' => fn () => reply(''),
    'html body' => fn () => reply('<html></html>'),
    'json without an address' => fn () => reply(['uf' => 'RJ']),
]);

it('resolves asynchronously', function () {
    fakeProviders(['viacep.com.br' => reply(stub('viacep/found.json'))]);

    expect(ViaCepProvider::getAsync('28895-190')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('viacep')
        ->cep->toBe('28895190');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    fakeProviders(['viacep.com.br' => reply(stub('viacep/not-found.json'))]);

    expect(fn () => ViaCepProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
