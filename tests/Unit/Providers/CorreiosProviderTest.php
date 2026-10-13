<?php

use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\CorreiosProvider;

it('maps a found address', function (string $body) {
    fakeProviders(['apps.correios.com.br' => reply($body)]);

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
    $fake = fakeProviders(['apps.correios.com.br' => reply(stub('correios/found.xml'))]);

    CorreiosProvider::get('05010-000');

    [$request, $options] = $fake->sent[0];

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://apps.correios.com.br/SigepMasterJPA/AtendeClienteService/AtendeCliente')
        ->and($request->getHeaderLine('Content-Type'))->toBe('text/xml;charset=UTF-8')
        ->and($request->getHeaderLine('Cache-Control'))->toBe('no-cache')
        ->and($options['timeout'])->toEqual(5)
        ->and((string) $request->getBody())->toContain('<cli:consultaCEP>', '<cep>05010000</cep>')
        ->and(simplexml_load_string((string) $request->getBody()))->not->toBeFalse();
});

it('fails when the cep cannot be resolved', function (Response $response) {
    fakeProviders(['apps.correios.com.br' => $response]);

    expect(fn () => CorreiosProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'soap fault' => fn () => reply(stub('correios/not-found.xml'), 500),
    'forbidden' => fn () => reply('', 403),
    'empty body' => fn () => reply(''),
    'malformed xml' => fn () => reply('<nintendo><zelda></mario></zelda>'),
    'envelope without an address' => fn () => reply('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body /></soap:Envelope>'),
    'address without a cep' => fn () => reply('<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/"><soap:Body><return><cep></cep></return></soap:Body></soap:Envelope>'),
]);

it('resolves asynchronously', function () {
    fakeProviders(['apps.correios.com.br' => reply(stub('correios/found.xml'))]);

    expect(CorreiosProvider::getAsync('05010-000')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('correios')
        ->cep->toBe('05010000');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    fakeProviders(['apps.correios.com.br' => reply(stub('correios/not-found.xml'), 500)]);

    expect(fn () => CorreiosProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
