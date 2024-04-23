<?php

namespace Saade\Cep\Providers;

use Exception;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Saade\Cep\DataObjects\CepResponse;

class BrasilApiProvider extends Provider
{
    protected static function getRequest(string $cep): Request
    {
        return new Request(
            method: 'GET',
            uri: "https://brasilapi.com.br/api/cep/v2/{$cep}",
            headers: [
                'Accept' => 'application/json',
                'Cache-Control' => 'no-cache',
            ]
        );
    }

    /**
     * @param  array  $data
     */
    protected static function handleErrors(Response $response, mixed $data): void
    {
        if ($response->getStatusCode() !== 200) {
            throw new Exception('Could not connect to Brasil Api provider.');
        }

        if (! $data) {
            throw new Exception('Could not parse Brasil Api provider response.');
        }
    }

    /**
     * @param  array  $data
     */
    protected static function mapResponse(Response $response, mixed $data): ?CepResponse
    {
        return new CepResponse(
            cep: data_get($data, 'cep'),
            state: data_get($data, 'state'),
            city: data_get($data, 'city'),
            neighborhood: data_get($data, 'neighborhood'),
            street: data_get($data, 'street'),
            provider: 'brasil-api',
        );
    }
}
