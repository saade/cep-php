<?php

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Orchestra\Testbench\TestCase;
use Psr\Http\Message\RequestInterface;
use Saade\Cep\Providers\Provider;

class FakeProviders
{
    /**
     * @var array<int, array{0: RequestInterface, 1: array<string, mixed>}>
     */
    public array $sent = [];

    /**
     * @var array<int, string>
     */
    public static array $stray = [];

    /**
     * @param  array<string, Response|Throwable>  $responses
     */
    public function __construct(protected array $responses) {}

    public function __invoke(RequestInterface $request, array $options): PromiseInterface
    {
        $response = $this->responses[$request->getUri()->getHost()] ?? null;

        if ($response === null) {
            static::$stray[] = (string) $request->getUri();

            return Create::rejectionFor(new LogicException("Unexpected request to {$request->getUri()}"));
        }

        $this->sent[] = [$request, $options];

        return $response instanceof Throwable ? Create::rejectionFor($response) : Create::promiseFor($response);
    }

    /**
     * @return array<int, string>
     */
    public function hosts(): array
    {
        return array_map(fn (array $sent) => $sent[0]->getUri()->getHost(), $this->sent);
    }

    /**
     * @return array<int, string>
     */
    public function urls(): array
    {
        return array_map(fn (array $sent) => (string) $sent[0]->getUri(), $this->sent);
    }
}

pest()->beforeEach(function () {
    FakeProviders::$stray = [];

    fakeProviders();
})->afterEach(function () {
    expect(FakeProviders::$stray)->toBe([]);
})->in('Unit');

pest()->beforeEach(fn () => Provider::useClient(null))->in('Live', 'Integration');

pest()->extend(TestCase::class)->in('Laravel');

function stub(string $file): string
{
    return file_get_contents(fixture($file));
}

function reply(string|array $body = '', int $status = 200): Response
{
    return new Response($status, [], is_array($body) ? json_encode($body) : $body);
}

/**
 * @param  array<string, Response|Throwable>  $responses
 */
function fakeProviders(array $responses = []): FakeProviders
{
    $fake = new FakeProviders($responses);

    Provider::useClient(new Client(['handler' => HandlerStack::create($fake)]));

    return $fake;
}
