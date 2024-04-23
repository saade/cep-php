<?php

namespace Saade\Cep\Providers;

use GuzzleHttp\Client;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;

use function Saade\Cep\Helpers\sanitizeCEP;

/**
 * @method static Request getRequest(string $cep)
 */
class Provider
{
    public static function get(string $cep): PromiseInterface
    {
        $cep = sanitizeCEP($cep);

        return (new Client(['timeout' => 30]))
            ->sendAsync(request: static::getRequest($cep))
            ->then(
                function (Response $response): ?CepResponse {
                    $data = static::processResponse($response);

                    static::handleErrors($response, $data);

                    return static::mapResponse($response, $data);
                }
            );
    }

    protected static function processResponse(Response $response): mixed
    {
        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * @throws Exception
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
    }

    protected static function mapResponse(Response $response, mixed $data): ?CepResponse
    {
        return null;
    }
}
