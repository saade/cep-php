<?php

use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\Cep;
use Saade\Cep\DataObjects\CepResponse;
use Saade\Cep\Providers\Provider;

abstract class LocalProvider extends Provider
{
    public static string $baseUrl;

    public static mixed $server;

    public static array $pipes;

    protected string $name;

    protected float $delay = 0;

    protected int $status = 200;

    protected function request(string $cep): RequestInterface
    {
        return new Request('GET', self::$baseUrl.'/?'.http_build_query([
            'provider' => $this->name,
            'delay' => $this->delay,
            'status' => $this->status,
        ]));
    }

    protected function handleErrors(ResponseInterface $response): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to local provider.');
        }
    }

    protected function toDTO(ResponseInterface $response): CepResponse
    {
        return new CepResponse($this->json($response)['provider'], '01001000', null, null, null, null);
    }
}

class FastProvider extends LocalProvider
{
    protected string $name = 'fast';

    protected float $delay = 0.1;
}

class SlowProvider extends LocalProvider
{
    protected string $name = 'slow';

    protected float $delay = 3;
}

class LateProvider extends LocalProvider
{
    protected string $name = 'late';

    protected float $delay = 0.5;
}

class FailingProvider extends LocalProvider
{
    protected string $name = 'failing';

    protected float $delay = 0.5;

    protected int $status = 500;
}

class OtherFailingProvider extends FailingProvider {}

beforeAll(function () {
    LocalProvider::$server = proc_open(
        [PHP_BINARY, __DIR__.'/../Fixtures/server.php'],
        [['pipe', 'r'], ['pipe', 'w'], ['file', '/dev/null', 'w']],
        $pipes,
    );

    LocalProvider::$pipes = $pipes;
    LocalProvider::$baseUrl = 'http://'.trim(fgets($pipes[1]));
});

afterAll(function () {
    array_map(fclose(...), LocalProvider::$pipes);

    proc_terminate(LocalProvider::$server);
    proc_close(LocalProvider::$server);
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
