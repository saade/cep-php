<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Saade\Cep\Cep;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\BrasilApiProvider;
use Saade\Cep\Providers\ViaCepProvider;

const OPEN_CEP_HOST = 'opencep.com';

const FALLBACK_HOSTS = [
    'apps.correios.com.br',
    'viacep.com.br',
    'cdn.apicep.com',
    'buscacepinter.correios.com.br',
    'brasilapi.com.br',
];

function found(string $host): Response
{
    return reply(stub(match ($host) {
        'opencep.com' => 'opencep/found.json',
        'apps.correios.com.br' => 'correios/found.xml',
        'viacep.com.br' => 'viacep/found.json',
        'cdn.apicep.com' => 'widenet/found.json',
        'buscacepinter.correios.com.br' => 'correios-alt/found.json',
        'brasilapi.com.br' => 'brasil-api/found.json',
    }));
}

function unreachable(string $host): ConnectException
{
    return new ConnectException('Connection timed out', new Request('GET', "https://{$host}"));
}

function mockProviders(array $found = [], array $unreachable = []): FakeProviders
{
    $responses = [
        'opencep.com' => reply(stub('opencep/not-found.json'), 404),
        'apps.correios.com.br' => reply('', 403),
        'viacep.com.br' => reply(stub('viacep/not-found.json')),
        'cdn.apicep.com' => reply(stub('widenet/not-found.json')),
        'buscacepinter.correios.com.br' => reply(stub('correios-alt/captcha.json')),
        'brasilapi.com.br' => reply(stub('brasil-api/not-found.json'), 404),
    ];

    foreach ($found as $host) {
        $responses[$host] = found($host);
    }

    foreach ($unreachable as $host) {
        $responses[$host] = unreachable($host);
    }

    return fakeProviders($responses);
}

it('returns the OpenCEP address without querying the other providers', function () {
    $fake = mockProviders(found: [OPEN_CEP_HOST, ...FALLBACK_HOSTS]);

    expect(Cep::get('28895-190'))->toArray()->toBe([
        'provider' => 'open-cep',
        'cep' => '28895190',
        'street' => 'Rua Nelson Pecegueiro do Amaral',
        'neighborhood' => 'Costazul',
        'city' => 'Rio das Ostras',
        'state' => 'RJ',
    ]);

    expect($fake->hosts())->toBe([OPEN_CEP_HOST]);
});

it('falls back to the other providers when OpenCEP cannot resolve the cep', function (string $host, string $provider, bool $openCepUnreachable) {
    $fake = mockProviders(found: [$host], unreachable: $openCepUnreachable ? [OPEN_CEP_HOST] : []);

    expect(Cep::get('28895-190'))
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe($provider)
        ->cep->not->toBeEmpty()
        ->street->not->toBeEmpty()
        ->neighborhood->not->toBeEmpty()
        ->city->not->toBeEmpty()
        ->state->not->toBeEmpty();

    expect($fake->hosts())->toBe([OPEN_CEP_HOST, ...FALLBACK_HOSTS]);
})->with([
    'correios' => ['apps.correios.com.br', 'correios'],
    'viacep' => ['viacep.com.br', 'viacep'],
    'widenet' => ['cdn.apicep.com', 'widenet'],
    'correios-alt' => ['buscacepinter.correios.com.br', 'correios-alt'],
    'brasil-api' => ['brasilapi.com.br', 'brasil-api'],
])->with([
    'OpenCEP not found' => false,
    'OpenCEP unreachable' => true,
]);

it('returns a complete address when several fallback providers resolve the cep', function () {
    mockProviders(found: FALLBACK_HOSTS);

    expect(Cep::get('28895-190'))
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBeIn(['correios', 'viacep', 'widenet', 'correios-alt', 'brasil-api'])
        ->cep->not->toBeEmpty()
        ->city->not->toBeEmpty()
        ->state->not->toBeEmpty();
});

it('returns null when no provider resolves the cep', function () {
    mockProviders();

    expect(Cep::get('00000-000'))->toBeNull();
});

it('returns null when every provider is unreachable', function () {
    mockProviders(unreachable: [OPEN_CEP_HOST, ...FALLBACK_HOSTS]);

    expect(Cep::get('28895-190'))->toBeNull();
});

it('survives unreachable providers when another one resolves the cep', function () {
    mockProviders(
        found: ['brasilapi.com.br'],
        unreachable: [OPEN_CEP_HOST, 'apps.correios.com.br', 'viacep.com.br', 'cdn.apicep.com', 'buscacepinter.correios.com.br'],
    );

    expect(Cep::get('28895-190'))->provider->toBe('brasil-api');
});

it('returns null without querying any provider when the cep has more than 8 digits', function () {
    $fake = mockProviders(found: [OPEN_CEP_HOST, ...FALLBACK_HOSTS]);

    expect(Cep::get('28895-1900'))->toBeNull()
        ->and($fake->sent)->toBe([]);
});

it('sends the sanitized cep to the providers', function (string $cep, string $expected) {
    $fake = mockProviders(found: ['brasilapi.com.br']);

    Cep::get($cep);

    expect($fake->urls())->toContain(
        "https://opencep.com/v1/{$expected}",
        "https://brasilapi.com.br/api/cep/v1/{$expected}",
    );
})->with([
    'masked' => ['28895-190', '28895190'],
    'leading zero dropped' => ['1001-000', '01001000'],
]);

it('races only the given providers', function () {
    $fake = mockProviders(found: [OPEN_CEP_HOST, ...FALLBACK_HOSTS]);

    expect(Cep::race('28895-190', [ViaCepProvider::class, BrasilApiProvider::class]))
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBeIn(['viacep', 'brasil-api'])
        ->cep->toBe('28895190');

    expect($fake->hosts())->toBe(['viacep.com.br', 'brasilapi.com.br']);
});

it('returns null from a race no provider wins', function () {
    mockProviders();

    expect(Cep::race('00000-000', [ViaCepProvider::class, BrasilApiProvider::class]))->toBeNull();
});
