<?php

use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\WideNetProvider;
use Saade\Cep\Requests\WideNetRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('maps a found address', function () {
    MockClient::global([WideNetRequest::class => MockResponse::make(stub('widenet/found.json'))]);

    expect(WideNetProvider::get('05010-000'))
        ->toBeInstanceOf(CepResponse::class)
        ->toArray()->toBe([
            'provider' => 'widenet',
            'cep' => '05010000',
            'street' => 'Rua Caiubi',
            'neighborhood' => 'Perdizes',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
});

it('requests the cep formatted with a dash', function (string $cep) {
    $mockClient = MockClient::global([WideNetRequest::class => MockResponse::make(stub('widenet/found.json'))]);

    WideNetProvider::get($cep);

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::GET)
        ->and($pendingRequest->getUrl())->toBe('https://cdn.apicep.com/file/apicep/05010-000.json')
        ->and($pendingRequest->headers()->get('Accept'))->toBe('application/json')
        ->and($pendingRequest->config()->get('timeout'))->toBe(5);
})->with([
    'masked' => '05010-000',
    'digits only' => '05010000',
    'leading zero dropped' => '5010000',
]);

it('fails when the cep cannot be resolved', function (MockResponse $response) {
    MockClient::global([WideNetRequest::class => $response]);

    expect(fn () => WideNetProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => MockResponse::make(stub('widenet/not-found.json')),
    'missing file' => fn () => MockResponse::make(['code' => 'not_found', 'message' => 'File with such name does not exist.', 'status' => 404], 404),
    'rate limited' => fn () => MockResponse::make(['status' => 429, 'ok' => false, 'message' => 'Blocked by flood cdn', 'statusText' => 'bad_request'], 429),
    'server error' => fn () => MockResponse::make('', 500),
    'empty body' => fn () => MockResponse::make(''),
    'html body' => fn () => MockResponse::make('<html></html>'),
]);

it('resolves asynchronously', function () {
    MockClient::global([WideNetRequest::class => MockResponse::make(stub('widenet/found.json'))]);

    expect(WideNetProvider::getAsync('05010-000')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('widenet')
        ->cep->toBe('05010000');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    MockClient::global([WideNetRequest::class => MockResponse::make(stub('widenet/not-found.json'))]);

    expect(fn () => WideNetProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
