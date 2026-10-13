<?php

use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\WideNetProvider;

it('maps a found address', function () {
    fakeProviders(['cdn.apicep.com' => reply(stub('widenet/found.json'))]);

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
    $fake = fakeProviders(['cdn.apicep.com' => reply(stub('widenet/found.json'))]);

    WideNetProvider::get($cep);

    [$request, $options] = $fake->sent[0];

    expect($request->getMethod())->toBe('GET')
        ->and((string) $request->getUri())->toBe('https://cdn.apicep.com/file/apicep/05010-000.json')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($options['timeout'])->toEqual(5);
})->with([
    'masked' => '05010-000',
    'digits only' => '05010000',
    'leading zero dropped' => '5010000',
]);

it('fails when the cep cannot be resolved', function (Response $response) {
    fakeProviders(['cdn.apicep.com' => $response]);

    expect(fn () => WideNetProvider::get('00000-000'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => reply(stub('widenet/not-found.json')),
    'missing file' => fn () => reply(['code' => 'not_found', 'message' => 'File with such name does not exist.', 'status' => 404], 404),
    'rate limited' => fn () => reply(['status' => 429, 'ok' => false, 'message' => 'Blocked by flood cdn', 'statusText' => 'bad_request'], 429),
    'server error' => fn () => reply('', 500),
    'empty body' => fn () => reply(''),
    'html body' => fn () => reply('<html></html>'),
]);

it('resolves asynchronously', function () {
    fakeProviders(['cdn.apicep.com' => reply(stub('widenet/found.json'))]);

    expect(WideNetProvider::getAsync('05010-000')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('widenet')
        ->cep->toBe('05010000');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    fakeProviders(['cdn.apicep.com' => reply(stub('widenet/not-found.json'))]);

    expect(fn () => WideNetProvider::getAsync('00000-000')->wait())->toThrow(Exception::class);
});
