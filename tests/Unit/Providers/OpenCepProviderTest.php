<?php

use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\OpenCepProvider;
use Saade\Cep\Requests\OpenCepRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('maps a found address', function () {
    MockClient::global([OpenCepRequest::class => MockResponse::make(stub('opencep/found.json'))]);

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
    $mockClient = MockClient::global([OpenCepRequest::class => MockResponse::make(stub('opencep/found.json'))]);

    OpenCepProvider::get('28895-190');

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::GET)
        ->and($pendingRequest->getUrl())->toBe('https://opencep.com/v1/28895190')
        ->and($pendingRequest->headers()->get('Accept'))->toBe('application/json')
        ->and($pendingRequest->config()->get('timeout'))->toBe(3);
});

it('fails when the cep cannot be resolved', function (MockResponse $response) {
    MockClient::global([OpenCepRequest::class => $response]);

    expect(fn () => OpenCepProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => MockResponse::make(stub('opencep/not-found.json'), 404),
    'server error' => fn () => MockResponse::make('', 500),
    'empty body' => fn () => MockResponse::make(''),
    'html body' => fn () => MockResponse::make('<html></html>'),
    'json without an address' => fn () => MockResponse::make(['error' => true]),
]);

it('resolves asynchronously', function () {
    MockClient::global([OpenCepRequest::class => MockResponse::make(stub('opencep/found.json'))]);

    expect(OpenCepProvider::getAsync('28895-190')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('open-cep')
        ->cep->toBe('28895190');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    MockClient::global([OpenCepRequest::class => MockResponse::make(stub('opencep/not-found.json'), 404)]);

    expect(fn () => OpenCepProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
