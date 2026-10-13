<?php

use Saade\Cep\Cep;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\Provider;
use Saloon\Enums\Method;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;

abstract class LocalRequest extends SoloRequest
{
    public static string $baseUrl;

    public static mixed $server;

    public static array $pipes;

    protected Method $method = Method::GET;

    protected string $name;

    protected float $responseDelay = 0;

    protected int $status = 200;

    public function __construct(protected string $cep)
    {
    }

    public function resolveEndpoint(): string
    {
        return static::$baseUrl.'/?'.http_build_query([
            'provider' => $this->name,
            'delay' => $this->responseDelay,
            'status' => $this->status,
        ]);
    }
}

class FastRequest extends LocalRequest
{
    protected string $name = 'fast';

    protected float $responseDelay = 0.1;
}

class SlowRequest extends LocalRequest
{
    protected string $name = 'slow';

    protected float $responseDelay = 3;
}

class LateRequest extends LocalRequest
{
    protected string $name = 'late';

    protected float $responseDelay = 0.5;
}

class FailingRequest extends LocalRequest
{
    protected string $name = 'failing';

    protected float $responseDelay = 0.5;

    protected int $status = 500;
}

abstract class LocalProvider extends Provider
{
    protected function handleErrors(Response $response): void
    {
        if (! $response->ok()) {
            throw new Exception('Could not connect to local provider.');
        }
    }

    protected function toDTO(Response $response): CepResponse
    {
        return new CepResponse($response->json('provider'), '01001000', null, null, null, null);
    }
}

class FastProvider extends LocalProvider
{
    protected static string $request = FastRequest::class;
}

class SlowProvider extends LocalProvider
{
    protected static string $request = SlowRequest::class;
}

class LateProvider extends LocalProvider
{
    protected static string $request = LateRequest::class;
}

class FailingProvider extends LocalProvider
{
    protected static string $request = FailingRequest::class;
}

class OtherFailingProvider extends LocalProvider
{
    protected static string $request = FailingRequest::class;
}

beforeAll(function () {
    LocalRequest::$server = proc_open(
        [PHP_BINARY, __DIR__.'/../Fixtures/server.php'],
        [['pipe', 'r'], ['pipe', 'w'], ['file', '/dev/null', 'w']],
        $pipes,
    );

    LocalRequest::$pipes = $pipes;
    LocalRequest::$baseUrl = 'http://'.trim(fgets($pipes[1]));
});

afterAll(function () {
    array_map(fclose(...), LocalRequest::$pipes);

    proc_terminate(LocalRequest::$server);
    proc_close(LocalRequest::$server);
});

it('returns the fastest provider without waiting for the slower ones', function () {
    $start = microtime(true);

    $response = Cep::race('01001-000', [SlowProvider::class, FastProvider::class]);

    expect($response)->provider->toBe('fast')
        ->and(microtime(true) - $start)->toBeLessThan(2);
});

it('queries the providers concurrently', function () {
    $start = microtime(true);

    $response = Cep::race('01001-000', [FailingProvider::class, OtherFailingProvider::class, LateProvider::class]);

    expect($response)->provider->toBe('late')
        ->and(microtime(true) - $start)->toBeLessThan(1.2);
});

it('keeps waiting for a slower provider when the faster ones fail', function () {
    expect(Cep::race('01001-000', [FailingProvider::class, LateProvider::class]))->provider->toBe('late');
});

it('returns null when every provider fails', function () {
    expect(Cep::race('01001-000', [FailingProvider::class, OtherFailingProvider::class]))->toBeNull();
});
