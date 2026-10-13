<?php

use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\CorreiosAltProvider;
use Saade\Cep\Requests\CorreiosAltRequest;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('maps a found address', function () {
    MockClient::global([CorreiosAltRequest::class => MockResponse::make(stub('correios-alt/found.json'))]);

    expect(CorreiosAltProvider::get('05010-000'))
        ->toBeInstanceOf(CepResponse::class)
        ->toArray()->toBe([
            'provider' => 'correios-alt',
            'cep' => '05010000',
            'street' => 'Rua Caiubi',
            'neighborhood' => 'Perdizes',
            'city' => 'São Paulo',
            'state' => 'SP',
        ]);
});

it('posts the sanitized cep as a form', function () {
    $mockClient = MockClient::global([CorreiosAltRequest::class => MockResponse::make(stub('correios-alt/found.json'))]);

    CorreiosAltProvider::get('05010-000');

    $pendingRequest = $mockClient->getLastPendingRequest();

    expect($pendingRequest->getMethod())->toBe(Method::POST)
        ->and($pendingRequest->getUrl())->toBe('https://buscacepinter.correios.com.br/app/endereco/carrega-cep-endereco.php')
        ->and($pendingRequest->headers()->get('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and($pendingRequest->headers()->get('Referer'))->toBe('https://buscacepinter.correios.com.br/app/endereco/index.php')
        ->and($pendingRequest->headers()->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($pendingRequest->config()->get('timeout'))->toBe(5)
        ->and($pendingRequest->body()->all())->toBe(['endereco' => '05010000', 'tipoCEP' => 'ALL']);
});

it('fails when the cep cannot be resolved', function (MockResponse $response) {
    MockClient::global([CorreiosAltRequest::class => $response]);

    expect(fn () => CorreiosAltProvider::get('99999-999'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => MockResponse::make(stub('correios-alt/not-found.json')),
    'captcha required' => fn () => MockResponse::make(stub('correios-alt/captcha.json')),
    'boolean error flag' => fn () => MockResponse::make(['erro' => true, 'mensagem' => 'CEP NAO ENCONTRADO', 'total' => 0, 'dados' => []]),
    'no results' => fn () => MockResponse::make(['erro' => false, 'mensagem' => 'DADOS NAO ENCONTRADOS', 'total' => 0, 'dados' => []]),
    'server error' => fn () => MockResponse::make('', 500),
    'empty body' => fn () => MockResponse::make(''),
    'html body' => fn () => MockResponse::make('<html></html>'),
]);

it('resolves asynchronously', function () {
    MockClient::global([CorreiosAltRequest::class => MockResponse::make(stub('correios-alt/found.json'))]);

    expect(CorreiosAltProvider::getAsync('05010-000')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('correios-alt')
        ->cep->toBe('05010000');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    MockClient::global([CorreiosAltRequest::class => MockResponse::make(stub('correios-alt/not-found.json'))]);

    expect(fn () => CorreiosAltProvider::getAsync('99999-999')->wait())->toThrow(Exception::class);
});
