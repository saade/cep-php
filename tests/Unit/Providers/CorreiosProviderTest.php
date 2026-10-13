<?php

use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\CorreiosProvider;
use Saade\Cep\Requests\CorreiosRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('maps a found address', function (string $body) {
    MockClient::global([CorreiosRequest::class => MockResponse::make($body)]);

    expect(CorreiosProvider::get('05010-000'))
        ->toBeInstanceOf(CepResponse::class)
        ->toArray()->toBe([
            'provider' => 'correios',
            'cep' => '05010000',
            'street' => 'Rua Caiubi',
            'neighborhood' => 'Perdizes',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
})->with([
    'utf-8 body' => fn () => stub('correios/found.xml'),
    'iso-8859-1 body' => fn () => iconv('UTF-8', 'ISO-8859-1', stub('correios/found.xml')),
]);

it('posts the sanitized cep in a soap envelope', function () {
    $mockClient = MockClient::global([CorreiosRequest::class => MockResponse::make(stub('correios/found.xml'))]);

    CorreiosProvider::get('05010-000');

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::POST)
        ->and($pendingRequest->getUrl())->toBe('https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente')
        ->and($pendingRequest->headers()->get('Content-Type'))->toBe('text/xml;charset=UTF-8')
        ->and($pendingRequest->config()->get('timeout'))->toBe(5)
        ->and((string) $pendingRequest->body())->toContain('<cli:consultaCEP>', '<cep>05010000</cep>')
        ->and(simplexml_load_string((string) $pendingRequest->body()))->not->toBeFalse();
});

it('fails when the cep cannot be resolved', function (MockResponse $response) {
    MockClient::global([CorreiosRequest::class => $response]);

    expect(fn () => CorreiosProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'soap fault' => fn () => MockResponse::make(stub('correios/not-found.xml'), 500),
    'forbidden' => fn () => MockResponse::make('', 403),
    'empty body' => fn () => MockResponse::make(''),
    'malformed xml' => fn () => MockResponse::make('<nintendo><zelda></mario></zelda>'),
    'envelope without an address' => fn () => MockResponse::make('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body /></soap:Envelope>'),
    'address without a cep' => fn () => MockResponse::make('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><return><cep></cep></return></soap:Body></soap:Envelope>'),
]);

it('resolves asynchronously', function () {
    MockClient::global([CorreiosRequest::class => MockResponse::make(stub('correios/found.xml'))]);

    expect(CorreiosProvider::getAsync('05010-000')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('correios')
        ->cep->toBe('05010000');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    MockClient::global([CorreiosRequest::class => MockResponse::make(stub('correios/not-found.xml'), 500)]);

    expect(fn () => CorreiosProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
