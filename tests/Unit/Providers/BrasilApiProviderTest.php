<?php

use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\BrasilApiProvider;
use Saade\Cep\Requests\BrasilApiRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('maps a found address', function () {
    MockClient::global([BrasilApiRequest::class => MockResponse::make(stub('brasil-api/found.json'))]);

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
    $mockClient = MockClient::global([BrasilApiRequest::class => MockResponse::make(stub('brasil-api/found.json'))]);

    BrasilApiProvider::get('28895-190');

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::GET)
        ->and($pendingRequest->getUrl())->toBe('https://brasilapi.com.br/api/cep/v1/28895190')
        ->and($pendingRequest->headers()->get('Accept'))->toBe('application/json')
        ->and($pendingRequest->config()->get('timeout'))->toBe(5);
});

it('fails when the cep cannot be resolved', function (MockResponse $response) {
    MockClient::global([BrasilApiRequest::class => $response]);

    expect(fn () => BrasilApiProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => MockResponse::make(stub('brasil-api/not-found.json'), 404),
    'validation error' => fn () => MockResponse::make(['type' => 'validation_error'], 400),
    'server error' => fn () => MockResponse::make('', 500),
    'empty body' => fn () => MockResponse::make(''),
    'html body' => fn () => MockResponse::make('<html></html>'),
    'json without an address' => fn () => MockResponse::make(['state' => 'RJ']),
]);

it('resolves asynchronously', function () {
    MockClient::global([BrasilApiRequest::class => MockResponse::make(stub('brasil-api/found.json'))]);

    expect(BrasilApiProvider::getAsync('28895-190')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('brasil-api')
        ->cep->toBe('28895190');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    MockClient::global([BrasilApiRequest::class => MockResponse::make(stub('brasil-api/not-found.json'), 404)]);

    expect(fn () => BrasilApiProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
