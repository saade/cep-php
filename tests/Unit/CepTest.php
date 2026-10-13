<?php

use Saade\Cep\Cep;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\BrasilApiProvider;
use Saade\Cep\Providers\ViaCepProvider;
use Saade\Cep\Requests\BrasilApiRequest;
use Saade\Cep\Requests\CorreiosAltRequest;
use Saade\Cep\Requests\CorreiosRequest;
use Saade\Cep\Requests\OpenCepRequest;
use Saade\Cep\Requests\ViaCepRequest;
use Saade\Cep\Requests\WideNetRequest;
use Saloon\Exceptions\Request\FatalRequestException;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\PendingRequest;
use Saloon\Http\Request;

const FALLBACK_REQUESTS = [
    CorreiosRequest::class,
    ViaCepRequest::class,
    WideNetRequest::class,
    CorreiosAltRequest::class,
    BrasilApiRequest::class,
];

function found(string $request): MockResponse
{
    return MockResponse::make(stub(match ($request) {
        OpenCepRequest::class => 'opencep/found.json',
        CorreiosRequest::class => 'correios/found.xml',
        ViaCepRequest::class => 'viacep/found.json',
        WideNetRequest::class => 'widenet/found.json',
        CorreiosAltRequest::class => 'correios-alt/found.json',
        BrasilApiRequest::class => 'brasil-api/found.json',
    }));
}

function unreachable(): MockResponse
{
    return MockResponse::make()->throw(
        fn (PendingRequest $pendingRequest) => new FatalRequestException(new Exception('Connection timed out'), $pendingRequest)
    );
}

function mockProviders(array $found = [], array $unreachable = []): MockClient
{
    $responses = [
        OpenCepRequest::class => MockResponse::make(stub('opencep/not-found.json'), 404),
        CorreiosRequest::class => MockResponse::make('', 403),
        ViaCepRequest::class => MockResponse::make(stub('viacep/not-found.json')),
        WideNetRequest::class => MockResponse::make(stub('widenet/not-found.json')),
        CorreiosAltRequest::class => MockResponse::make(stub('correios-alt/captcha.json')),
        BrasilApiRequest::class => MockResponse::make(stub('brasil-api/not-found.json'), 404),
    ];

    foreach ($found as $request) {
        $responses[$request] = found($request);
    }

    foreach ($unreachable as $request) {
        $responses[$request] = unreachable();
    }

    return MockClient::global($responses);
}

it('returns the OpenCEP address without querying the other providers', function () {
    $mockClient = mockProviders(found: [OpenCepRequest::class, ...FALLBACK_REQUESTS]);

    expect(Cep::get('28895-190'))->toArray()->toBe([
        'provider' => 'open-cep',
        'cep' => '28895190',
        'street' => 'Rua Nelson Pecegueiro do Amaral',
        'neighborhood' => 'Costazul',
        'city' => 'Rio das Ostras',
        'state' => 'RJ',
    ]);

    $mockClient->assertSentCount(1);
    $mockClient->assertSent(OpenCepRequest::class);
});

it('falls back to the other providers when OpenCEP cannot resolve the cep', function (string $request, string $provider, bool $openCepUnreachable) {
    $mockClient = mockProviders(found: [$request], unreachable: $openCepUnreachable ? [OpenCepRequest::class] : []);

    expect(Cep::get('28895-190'))
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBe($provider)
        ->cep->not->toBeEmpty()
        ->street->not->toBeEmpty()
        ->neighborhood->not->toBeEmpty()
        ->city->not->toBeEmpty()
        ->state->not->toBeEmpty();

    foreach (FALLBACK_REQUESTS as $fallback) {
        $mockClient->assertSent($fallback);
    }
})->with([
    'correios' => [CorreiosRequest::class, 'correios'],
    'viacep' => [ViaCepRequest::class, 'viacep'],
    'widenet' => [WideNetRequest::class, 'widenet'],
    'correios-alt' => [CorreiosAltRequest::class, 'correios-alt'],
    'brasil-api' => [BrasilApiRequest::class, 'brasil-api'],
])->with([
    'OpenCEP not found' => false,
    'OpenCEP unreachable' => true,
]);

it('returns a complete address when several fallback providers resolve the cep', function () {
    mockProviders(found: FALLBACK_REQUESTS);

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
    mockProviders(unreachable: [OpenCepRequest::class, ...FALLBACK_REQUESTS]);

    expect(Cep::get('28895-190'))->toBeNull();
});

it('survives unreachable providers when another one resolves the cep', function () {
    mockProviders(
        found: [BrasilApiRequest::class],
        unreachable: [OpenCepRequest::class, CorreiosRequest::class, ViaCepRequest::class, WideNetRequest::class, CorreiosAltRequest::class],
    );

    expect(Cep::get('28895-190'))->provider->toBe('brasil-api');
});

it('returns null without querying any provider when the cep has more than 8 digits', function () {
    $mockClient = mockProviders(found: [OpenCepRequest::class, ...FALLBACK_REQUESTS]);

    expect(Cep::get('28895-1900'))->toBeNull();

    $mockClient->assertNothingSent();
});

it('sends the sanitized cep to the providers', function (string $cep, string $expected) {
    $mockClient = mockProviders(found: [BrasilApiRequest::class]);

    Cep::get($cep);

    $mockClient->assertSent(
        fn (Request $request) => $request instanceof OpenCepRequest
            && $request->resolveEndpoint() === "https://opencep.com/v1/{$expected}"
    );

    $mockClient->assertSent(
        fn (Request $request) => $request instanceof BrasilApiRequest
            && $request->resolveEndpoint() === "https://brasilapi.com.br/api/cep/v1/{$expected}"
    );
})->with([
    'masked' => ['28895-190', '28895190'],
    'leading zero dropped' => ['1001-000', '01001000'],
]);

it('races only the given providers', function () {
    $mockClient = mockProviders(found: [OpenCepRequest::class, ...FALLBACK_REQUESTS]);

    expect(Cep::race('28895-190', [ViaCepProvider::class, BrasilApiProvider::class]))
        ->toBeInstanceOf(CepResponse::class)
        ->provider->toBeIn(['viacep', 'brasil-api'])
        ->cep->toBe('28895190');

    $mockClient->assertSentCount(2);
    $mockClient->assertSent(ViaCepRequest::class);
    $mockClient->assertSent(BrasilApiRequest::class);
});

it('returns null from a race no provider wins', function () {
    mockProviders();

    expect(Cep::race('00000-000', [ViaCepProvider::class, BrasilApiProvider::class]))->toBeNull();
});
