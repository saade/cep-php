<?php

namespace Saade\Cep\Providers;

use GuzzleHttp\Promise\PromiseInterface;
use Saade\Cep\DataObjects\CepResponse;
use Saloon\Http\Response;
use Saloon\Http\SoloRequest;

use function Saade\Cep\Helpers\sanitizeCEP;

/**
 * @property SoloRequest $request
 */
class Provider
{
    public static function get(string $cep): PromiseInterface
    {
        $cep = sanitizeCEP($cep);

        return static::$request::make($cep)
            ->sendAsync()
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
        return $response->json();
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
