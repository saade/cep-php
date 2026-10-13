<?php

use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\CorreiosAltProvider;

it('maps a found address', function () {
    fakeProviders(['buscacepinter.correios.com.br' => reply(stub('correios-alt/found.json'))]);

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
    $fake = fakeProviders(['buscacepinter.correios.com.br' => reply(stub('correios-alt/found.json'))]);

    CorreiosAltProvider::get('05010-000');

    [$request, $options] = $fake->sent[0];

    parse_str((string) $request->getBody(), $form);

    expect($request->getMethod())->toBe('POST')
        ->and((string) $request->getUri())->toBe('https://buscacepinter.correios.com.br/app/endereco/carrega-cep-endereco.php')
        ->and($request->getHeaderLine('Content-Type'))->toBe('application/x-www-form-urlencoded')
        ->and($request->getHeaderLine('Accept'))->toBe('application/json')
        ->and($request->getHeaderLine('Referer'))->toBe('https://buscacepinter.correios.com.br/app/endereco/index.php')
        ->and($request->getHeaderLine('Referrer-Policy'))->toBe('strict-origin-when-cross-origin')
        ->and($options['timeout'])->toEqual(5)
        ->and($form)->toBe(['endereco' => '05010000', 'tipoCEP' => 'ALL']);
});

it('fails when the cep cannot be resolved', function (Response $response) {
    fakeProviders(['buscacepinter.correios.com.br' => $response]);

    expect(fn () => CorreiosAltProvider::get('99999-999'))->toThrow(Exception::class);
})->with([
    'not found' => fn () => reply(stub('correios-alt/not-found.json')),
    'captcha required' => fn () => reply(stub('correios-alt/captcha.json')),
    'boolean error flag' => fn () => reply(['erro' => true, 'mensagem' => 'CEP NAO ENCONTRADO', 'total' => 0, 'dados' => []]),
    'no results' => fn () => reply(['erro' => false, 'mensagem' => 'DADOS NAO ENCONTRADOS', 'total' => 0, 'dados' => []]),
    'server error' => fn () => reply('', 500),
    'empty body' => fn () => reply(''),
    'html body' => fn () => reply('<html></html>'),
]);

it('resolves asynchronously', function () {
    fakeProviders(['buscacepinter.correios.com.br' => reply(stub('correios-alt/found.json'))]);

    expect(CorreiosAltProvider::getAsync('05010-000')->wait())
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe('correios-alt')
        ->cep->toBe('05010000');
});

it('rejects asynchronously when the cep cannot be resolved', function () {
    fakeProviders(['buscacepinter.correios.com.br' => reply(stub('correios-alt/not-found.json'))]);

    expect(fn () => CorreiosAltProvider::getAsync('99999-999')->wait())->toThrow(Exception::class);
});
