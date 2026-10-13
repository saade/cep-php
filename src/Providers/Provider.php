<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Saade\Cep\DataObjects\CepResponse;

use function Saade\Cep\Helpers\sanitizeCEP;

abstract class Provider
{
    protected static ?ClientInterface $client = null;

    protected float $timeout = 5;

    public static function useClient(?ClientInterface $client): void
    {
        self::$client = $client;
    }

    public static function get(string $cep): ?CepResponse
    {
        return (new static)->send($cep);
    }

    public static function getAsync(string $cep): PromiseInterface
    {
        return (new static)->sendAsync($cep);
    }

    public function send(string $cep): ?CepResponse
    {
        $response = $this->client()->send($this->request(sanitizeCEP($cep)), $this->options());

        $this->handleErrors($response);

        return $this->toDTO($response);
    }

    public function sendAsync(string $cep): PromiseInterface
    {
        return $this->client()
            ->sendAsync($this->request(sanitizeCEP($cep)), $this->options())
            ->then(
                function (ResponseInterface $response): ?CepResponse {
                    $this->handleErrors($response);

                    return $this->toDTO($response);
                }
            );
    }

    abstract protected function request(string $cep): RequestInterface;

    /**
     * @throws Exception
     */
    abstract protected function handleErrors(ResponseInterface $response): void;

    abstract protected function toDTO(ResponseInterface $response): CepResponse;

    protected function client(): ClientInterface
    {
        return self::$client ??= new Client;
    }

    /**
     * @return array<string, mixed>
     */
    protected function options(): array
    {
        return [
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::HTTP_ERRORS => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function json(ResponseInterface $response): array
    {
        $data = json_decode((string) $response->getBody(), true);

        return is_array($data) ? $data : [];
    }
}
