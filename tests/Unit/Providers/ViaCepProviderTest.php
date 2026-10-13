<?php

use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\ViaCepProvider;
use Saade\Cep\Requests\ViaCepRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('maps a found address', function () {
    MockClient::global([ViaCepRequest::class => MockResponse::make(stub('viacep/found.json'))]);

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
    $mockClient = MockClient::global([ViaCepRequest::class => MockResponse::make(stub('viacep/found.json'))]);

    ViaCepProvider::get('28895-190');

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::GET)
        ->and($pendingRequest->getUrl())->toBe('https://viacep.com.br/ws/28895190/json/')
        ->and($pendingRequest->headers()->get('Accept'))->toBe('application/json')
        ->and($pendingRequest->config()->get('timeout'))->toBe(5);
});

it('fails when the cep cannot be resolved', function (MockResponse $response) {
    MockClient::global([ViaCepRequest::class => $response]);

    expect(fn () => ViaCepProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => MockResponse::make(stub('viacep/not-found.json')),
    'not found with boolean flag' => fn () => MockResponse::make(['erro' => true]),
    'bad request' => fn () => MockResponse::make('<h2>Bad Request (400)</h2>', 400),
    'server error' => fn () => MockResponse::make('', 500),
    'empty body' => fn () => MockResponse::make(''),
    'html body' => fn () => MockResponse::make('<html></html>'),
    'json without an address' => fn () => MockResponse::make(['uf' => 'RJ']),
]);

it('resolves asynchronously', function () {
    MockClient::global([ViaCepRequest::class => MockResponse::make(stub('viacep/found.json'))]);

    expect(ViaCepProvider::getAsync('28895-190')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('viacep')
        ->cep->toBe('28895190');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    MockClient::global([ViaCepRequest::class => MockResponse::make(stub('viacep/not-found.json'))]);

    expect(fn () => ViaCepProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
